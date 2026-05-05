# Phase 02 – Reglas Condicionales

## Objetivo

Introducir reglas de negocio transversales que modifican el precio base: descuentos por volumen, promociones combinadas, precio mínimo garantizado y validaciones dependientes. Aquí empieza a tensionarse el diseño de cada arquitectura.

---

## Reglas de negocio

| Regla | Fórmula |
|-------|---------|
| Descuento volumen (≥7 noches) | `base_price × 10%` |
| Descuento volumen (≥14 noches) | `base_price × 20%` |
| Promoción combinada (productos 1+2) | `base_price × 5%` |
| Precio mínimo garantizado | Si `base_price - descuentos < 500`, rechazar con 422 |
| Validación extra Spa | Spa requiere mínimo 3 noches |

Las reglas son **acumulables**: una reserva de 14 noches con productos 1+2 obtiene volumen-20% + combined-promo-5%.

---

## Cálculo de referencia

### Payload: 14 noches + ambos productos

```json
{
  "products": [
    {
      "product_id": 1,
      "dates": ["2026-03-01", "2026-03-02", "2026-03-03", "2026-03-04", "2026-03-05", "2026-03-06", "2026-03-07", "2026-03-08", "2026-03-09", "2026-03-10", "2026-03-11", "2026-03-12", "2026-03-13", "2026-03-14"]
    },
    {
      "product_id": 2,
      "dates": ["2026-03-01", "2026-03-02", "2026-03-03", "2026-03-04", "2026-03-05", "2026-03-06", "2026-03-07", "2026-03-08", "2026-03-09", "2026-03-10", "2026-03-11", "2026-03-12", "2026-03-13", "2026-03-14"],
      "extras": [
        { "extra_id": 10, "dates": ["2026-03-01", "2026-03-02"] },
        { "extra_id": 11 }
      ]
    }
  ]
}
```

### Desglose

| Concepto | Cálculo | Resultado |
|----------|---------|-----------|
| Producto 1 (Habitación estándar) | $100 × 14 noches | $1,400 |
| Producto 2 (Habitación premium) | $180 × 14 noches | $2,520 |
| **base_price** | | **$3,920** |
| Extra 10 (Desayuno, per_night) | $20 × 2 noches | $40 |
| Extra 11 (Spa, per_stay) | $50 × 1 | $50 |
| **extras** | | **$90** |
| Descuento volumen (20%) | $3,920 × 0.20 | -$784 |
| Promoción combinada (5%) | $3,920 × 0.05 | -$196 |
| **discount_amount** | | **-$980** |
| **discount_reason** | | `volume-20% + combined-promo-5%` |

### Response esperado

```json
{
  "id": 1,
  "type": "multi-product",
  "base_price": 3920,
  "discount_amount": 980,
  "discount_reason": "volume-20% + combined-promo-5%",
  "extras": [
    { "name": "Habitación estándar - Hotel A - Desayuno", "price": 40 },
    { "name": "Habitación estándar - Hotel A - Spa", "price": 50 }
  ]
}
```

---

## Métricas comparativas

| Métrica | A01 Monolithic | A02 Repository | A03 Strategy | A04 Decorator |
|---------|----------------|----------------|--------------|---------------|
| Archivos | 6 | 10 | 15 | 17 |
| Líneas de código | 326 | 407 | 523 | 671 |
| Controlador | 123 líneas | 30 líneas | 32 líneas | 32 líneas |
| Servicio/Lógica | 123 líneas (controller) | 106 líneas (service) | 88 líneas (service) | 162 líneas (service) |
| Lógica de descuentos | Inline en controller | Inline en service | Inline en service | Decorators (VolumeDiscount, CombinedPromo) |
| Excepción de dominio | No | MinimumPriceException | MinimumPriceException | MinimumPriceException |
| Test de equivalencia | ✅ | ✅ | ✅ | ✅ |

### Crecimiento respecto a Phase 01

| Métrica | A01 | A02 | A03 | A04 |
|---------|-----|-----|-----|-----|
| +Archivos | +1 | +2 | +2 | +4 |
| +Líneas | +108 (+50%) | +138 (+51%) | +152 (+41%) | +248 (+59%) |
| +Controlador | +40 líneas | +1 línea | +1 línea | 0 líneas |

---

## Conclusiones

### A01 – Monolithic Eloquent
- **El punto de quiebre:** El controlador pasa de 83 a 123 líneas. Mezcla precio base, extras, 2 tipos de descuento, precio mínimo y persistencia en un solo método.
- **Todo visible, todo acoplado:** La ventaja de "un solo archivo" se convierte en desventaja cuando hay que rastrear interacciones entre reglas.
- **Observación:** Phase 02 confirma la predicción de Phase 01: cada regla nueva añade complejidad ciclomática al mismo método.

### A02 – Repository Pattern
- **Controlador protegido:** 30 líneas, inmutable desde Phase 01. La separación controller/service absorbe toda la complejidad.
- **Servicio "Dios" incipiente:** 106 líneas con lógica procedural. La ventaja es que es testeable con mocks.
- **Observación:** A02 demuestra que el Repository Pattern no resuelve la complejidad algorítmica, solo la mueve de lugar.

### A03 – Strategy + Polymorphism
- **Strategy Pattern aún no justificado:** Las reglas de Phase 02 no son de "tipo de reserva", son transversales. El Strategy Pattern no aporta ventaja aquí respecto a A02.
- **Deuda arquitectónica persistente:** `Reservations` abstracta sin uso, `MultiProductReservation` heredando de Eloquent.
- **Observación:** A03 brilla en Phase 03 (comportamiento por tipo), no en Phase 02 (reglas transversales).

### A04 – Decorator Domain
- **Descuentos como objetos:** `VolumeDiscountDecorator` y `CombinedPromoDecorator` encapsulan cada regla. Open/Closed Principle aplicado.
- **Precio más alto en abstracción:** 17 archivos, 671 líneas. El servicio orquestador crece a 162 líneas construyendo decoradores.
- **Descuentos secuenciales:** En Phase 02, los decoradores aplican descuentos secuencialmente (cada uno sobre el resultado del anterior), mientras que las otras arquitecturas aplican aditivamente (cada uno sobre el precio original). Esto produce resultados numéricamente diferentes.
- **Observación:** La extensibilidad del Decorator se justificará en Phase 03 y 04, pero el coste en complejidad ya es significativo.

---

## Verificación

Todos los tests funcionales pasan en las 4 arquitecturas.

---

## Tensión arquitectónica

Phase 02 es donde se empieza a notar la diferencia entre las arquitecturas:

- **A01** sufre directamente (controlador de 123 líneas).
- **A02** mueve el problema al servicio (106 líneas).
- **A03** paga el precio del Strategy sin beneficio directo (523 líneas para reglas transversales).
- **A04** invierte en Decorators que serán útiles en fases posteriores (671 líneas, pero cada regla aislada).

La pregunta es: ¿cuánta complejidad predecible vale la pena invertir vs. reaccionar a medida que crece?
