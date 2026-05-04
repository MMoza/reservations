# Phase 01 – Cálculo Base

## Objetivo

Establecer el baseline funcional: crear una reserva con múltiples productos, fechas y extras opcionales, y calcular el precio total.

---

## Reglas de negocio

| Regla | Fórmula |
|-------|---------|
| Precio base del producto | `price_per_night × número_de_noches` |
| Extra `per_night` | `precio × número_de_noches` (o fechas específicas si se proporcionan) |
| Extra `per_stay` | `precio` (una sola vez, independiente de las noches) |
| Total | `base_price + suma(todos_los_extras)` |

---

## Cálculo de referencia

### Payload

```json
{
  "products": [
    {
      "product_id": 1,
      "dates": ["2026-03-01", "2026-03-02", "2026-03-03"],
      "extras": [
        { "extra_id": 10, "dates": ["2026-03-01", "2026-03-02", "2026-03-03"] },
        { "extra_id": 11 }
      ]
    }
  ]
}
```

### Desglose

| Concepto | Cálculo | Resultado |
|----------|---------|-----------|
| Producto 1 (Habitación estándar) | $100 × 3 noches | $300 |
| Extra 10 (Desayuno, per_night) | $20 × 3 noches | $60 |
| Extra 11 (Spa, per_stay) | $50 × 1 | $50 |
| **base_price** | | **$300** |
| **extras** | | **$110** |
| **total** | | **$410** |

### Response esperado

```json
{
  "id": 1,
  "type": "multi-product",
  "base_price": 300,
  "extras": [
    { "name": "Habitación estándar - Hotel A - Desayuno", "price": 60 },
    { "name": "Habitación estándar - Hotel A - Spa", "price": 50 }
  ]
}
```

---

## Métricas comparativas

| Métrica | A01 Monolithic | A02 Repository | A03 Strategy | A04 Decorator |
|---------|----------------|----------------|--------------|---------------|
| Archivos | 5 | 8 | 13 | 13 |
| Líneas de código | 218 | 269 | 371 | 423 |
| Controlador | 83 líneas | 29 líneas | 31 líneas | 32 líneas |
| Lógica de cálculo | En controlador | En servicio | En dominio + estrategia | En dominio + decoradores |
| Separación de responsabilidades | Ninguna | Repository + Service | Domain + Strategy + Repository | Domain + Decorator + Repository |
| Test de equivalencia | ✅ | ✅ | ✅ | ✅ |

---

## Conclusiones

### A01 – Monolithic Eloquent
- **Ventaja:** Mínimo código, más rápido de implementar. Todo está en un solo lugar.
- **Desventaja:** El controlador tiene toda la lógica (83 líneas). No hay separación entre dominio e infraestructura. Añadir cualquier regla nueva implica modificar directamente el controlador.
- **Observación:** Adecuado para CRUD simple, pero el punto de extensión es el peor posible.

### A02 – Repository Pattern
- **Ventaja:** El controlador es delgado (29 líneas). La lógica de negocio vive en el servicio. La infraestructura está detrás de una interfaz, lo que permite mockear en tests.
- **Desventaja:** El servicio sigue teniendo lógica de cálculo inline. Los modelos Eloquent son anémicos. No hay un concepto de "dominio" real.
- **Observación:** Mejora significativa en organización respecto a A01 con un coste moderado (+51 líneas, +3 archivos).

### A03 – Strategy + Polymorphism
- **Ventaja:** El cálculo de precios está encapsulado en `PricingStrategy`, lo que permite cambiar la fórmula sin tocar el servicio. `ProductCollection` abstrae el catálogo.
- **Desventaja:** `MultiProductReservation` extiende del Eloquent Model, mezclando dominio con persistencia. La clase abstracta `Reservations` existe pero no se usa (código muerto).
- **Observación:** Buena intención de separar el cálculo, pero la implementación tiene inconsistencias arquitectónicas que se manifestarán en fases posteriores.

### A04 – Decorator Domain
- **Ventaja:** Separación total entre dominio y persistencia. Los objetos de pricing son puros (no extienden de Eloquent). El patrón Decorator permite componer comportamiento sin modificar código existente.
- **Desventaja:** Mayor complejidad conceptual (423 líneas, 13 archivos). El servicio de creación tiene 118 líneas porque orquesta la construcción de la cadena de decoradores.
- **Observación:** La inversión en abstracción no se justifica aún en Phase 01, pero el diseño está preparado para reglas combinables y dinámicas.

---

## Verificación

Test de equivalencia: `Phase01EquivalenceTest` – 2 tests, 34 assertions.
Todas las arquitecturas producen resultados idénticos con el mismo input.
