# Phase 04 – Reglas Combinables y Dinámicas

## Objetivo

Introducir reglas que combinan contexto temporal (antelación de reserva, temporada) con el cálculo de precio: early booking discount (reservar con antelación = descuento) y seasonal surcharge (temporada alta = recargo). Se pone a prueba la extensibilidad real de cada arquitectura.

---

## Reglas de negocio

| Regla | Condición | Efecto |
|-------|-----------|--------|
| Early booking 30 días | Primera fecha ≥ 30 días en el futuro | 5% descuento sobre base_price |
| Early booking 60 días | Primera fecha ≥ 60 días en el futuro | 10% descuento sobre base_price |
| Seasonal surcharge | Alguna fecha en julio o agosto | 15% recargo sobre base_price |
| Acumulabilidad | Early booking se acumula con volumen y combinada | Sí, aditivo |
| Surcharge aplica | Una sola vez (primera fecha en high season) | 15% sobre base_price total |

---

## Cálulo de referencia

### Payload: Early booking 30 días, temporada baja

```json
{
  "products": [
    {
      "product_id": 1,
      "dates": ["2026-06-10", "2026-06-11", "2026-06-12", "2026-06-13", "2026-06-14", "2026-06-15", "2026-06-16"]
    }
  ]
}
```

### Desglose

| Concepto | Cálculo | Resultado |
|----------|---------|-----------|
| Producto 1 (Habitación estándar) | $100 × 7 noches | $700 |
| **base_price** | | **$700** |
| Descuento volumen (10%, ≥7 noches) | $700 × 0.10 | -$70 |
| Early booking (5%, ≥30 días) | $700 × 0.05 | -$35 |
| Seasonal surcharge | Sin fecha en julio/agosto | $0 |
| **discount_amount** | | **-$105** |
| **discount_reason** | | `volume-10% + early-booking-5%` |
| **early_booking_discount_amount** | | **$35** |
| **seasonal_surcharge_amount** | | **$0** |

### Response esperado

```json
{
  "id": 1,
  "type": "multi-product",
  "base_price": 700,
  "discount_amount": 105,
  "discount_reason": "volume-10% + early-booking-5%",
  "tax_amount": 70,
  "tax_rate": "Tax 10%",
  "commission_amount": 0,
  "early_booking_discount_amount": 35,
  "seasonal_surcharge_amount": 0,
  "extras": []
}
```

---

## Métricas comparativas

| Métrica | A01 Monolithic | A02 Repository | A03 Strategy | A04 Decorator |
|---------|----------------|----------------|--------------|---------------|
| Archivos | 6 | 10 | 14 | 22 |
| Líneas de código | 466 | 587 | 775 | 1139 |
| Controlador | 184 líneas | 30 líneas | 32 líneas | 32 líneas |
| Servicio/Lógica | 184 líneas (controller) | 172 líneas (service) | 123 líneas (service) | 99 + 152 líneas (service + builder) |
| Early booking | Inline en controller | Inline en service | Delegado en estrategia | EarlyBookingDecorator |
| Seasonal surcharge | Inline con bucle + break | Inline con bucle + break | Delegado en estrategia | SeasonalSurchargeDecorator |
| Test de equivalencia | ✅ | ✅ | ✅ | ✅ |

### Crecimiento respecto a Phase 03

| Métrica | A01 | A02 | A03 | A04 |
|---------|-----|-----|-----|-----|
| +Archivos | 0 | 0 | -1 | +3 |
| +Líneas | +68 (+17%) | +84 (+17%) | +48 (+7%) | +261 (+30%) |
| +Controlador | +43 líneas | 0 líneas | 0 líneas | 0 líneas |

### Crecimiento acumulado (Phase 01 → Phase 04)

| Métrica | A01 | A02 | A03 | A04 |
|---------|-----|-----|-----|-----|
| Archivos | +1 | +2 | +1 | +9 |
| Líneas | +248 (+114%) | +318 (+118%) | +404 (+109%) | +716 (+169%) |
| Controlador | +101 líneas | +1 línea | +1 línea | 0 líneas |

---

## Conclusiones

### A01 – Monolithic Eloquent
- **184 líneas en el controlador:** El punto de no retorno. 13 responsabilidades en un solo método. Early booking discount se calcula inline y el `early_booking_discount_amount` se extrae parseando el string `discount_reason` (`str_starts_with` + `explode`), un hack frágil.
- **Evidencia del colapso:** Cada fase añadió 40-70 líneas al controlador. La previsión es clara: Phase 05 lo llevaría a 250+ líneas.
- **Observación:** A01 demuestra que la "simplicidad" inicial tiene un coste exponencial.

### A02 – Repository Pattern
- **172 líneas en el servicio:** Mismo patrón que A01 pero en el servicio. El controlador de 30 líneas es un espejismo: toda la complejidad se desplazó, no se resolvió.
- **Servicio "Dios" consolidado:** 13 responsabilidades en `createReservation()`. Añadir Phase 05 lo llevaría a 250+ líneas.
- **Observación:** A02 demuestra que Repository Pattern desacopla infraestructura pero no reduce complejidad algorítmica.

### A03 – Strategy + Polymorphism
- **123 líneas en el servicio:** El más delgado de los services (excluyendo A04). `Phase04PricingStrategy` encapsula early booking y seasonal surcharge.
- **Duplicación de estrategias:** `Phase04PricingStrategy` repite toda la lógica de `BasicPricingStrategy` para añadir 3 métodos. Esto no escala: Phase 05 requeriría `Phase05PricingStrategy` duplicando todo de nuevo.
- **Menor crecimiento:** +48 líneas (+7%) es el crecimiento más bajo de las 4 arquitecturas, pero a costa de duplicar la estrategia.
- **Observación:** A03 ofrece buen equilibrio pero necesita refactorización del patrón Strategy para evitar duplicación.

### A04 – Decorator Domain
- **Service + Builder:** `CreateReservationService` (99 líneas) orquesta, `ReservationPriceBuilder` (152 líneas) compone. La extracción del Builder fue el refactor clave de esta fase.
- **Decorators para Phase 04:** `EarlyBookingDecorator` (70 líneas) y `SeasonalSurchargeDecorator` (52 líneas) encapsulan cada regla. Phase 05 requeriría solo un nuevo Decorator + una línea en el Builder.
- **originalBasePrice():** Corrección de diseño necesaria. Todos los decoradores de porcentaje calculan sobre el precio original, no sobre precios ya descontados.
- **Mayor inversión, mejor extensibilidad:** 22 archivos, 1139 líneas. El coste es alto pero añadir reglas nuevas no hace crecer el service ni el builder significativamente.
- **Observación:** A04 demuestra que Builder + Decorator escala horizontalmente: cada regla nueva es un archivo nuevo, no código modificado.

---

## Verificación

77 tests, 376 assertions. Todas las arquitecturas producen resultados idénticos con el mismo input.

---

## Ranking por criterio (Phase 04)

| Criterio | 🥇 Mejor | 🥈 Segundo | 🥉 Tercero | Peor |
|----------|----------|------------|------------|------|
| Simplicidad | A01 (6 archivos) | A02 (10 archivos) | A03 (14 archivos) | A04 (22 archivos) |
| Controlador delgado | A02/A03/A04 (30-32) | - | - | A01 (184) |
| Servicio delgado | A04 (99) | A03 (123) | A02 (172) | A01 (184) |
| Testeabilidad | A04 (dominio puro) | A03 (estrategia mockeable) | A02 (service mockeable) | A01 (requiere BD) |
| Extensibilidad | A04 (nuevo Decorator) | A03 (nueva Strategy) | A02 (modificar service) | A01 (modificar controller) |
| Fragilidad | A04 (baja) | A03 (media) | A02 (media-alta) | A01 (alta, parsing de string) |

---

## Tensión arquitectónica

Phase 04 es donde cada arquitectura muestra su patrón real de crecimiento:

- **A01 y A02** crecen linealmente (+68 y +84 líneas). Cada regla nueva es código inline. El límite es predecible pero doloroso.
- **A03** crece poco (+48 líneas) pero a costa de duplicar la estrategia. El patrón Strategy necesita composición, no versiones.
- **A04** crece más (+261 líneas) pero la inversión es en abstracciones reutilizables. El Builder de 152 líneas es genérico: Phase 05 apenas lo tocará.

La pregunta final es: **¿cuánta complejidad estructural estás dispuesto a pagar para reducir la complejidad de cambio?**

- Si el dominio es estable → A02 es suficiente.
- Si hay tipos de producto con fórmulas distintas → A03 vale la pena.
- Si las reglas de pricing cambian frecuentemente → A04 se paga sola.
