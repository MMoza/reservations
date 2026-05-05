# Phase 03 – A01 Monolithic Eloquent

## Resumen

Comportamiento polimórfico: las reservas de tipo "hotel" y "event" aplican fórmulas distintas de impuestos y comisión. En esta arquitectura, toda la lógica se implementa directamente en el controlador, usando `product_type` del catálogo para decidir qué reglas aplicar. Se añaden 3 nuevas columnas a `reservations` (`tax_amount`, `tax_rate`, `commission_amount`).

---

## Estructura

```
Phase_03/
├── Catalog/
│   ├── ProductCatalog.php              (58 líneas) – Agregado product_type a productos
│   └── PricingRules.php                (62 líneas) – Agregados taxRates, commissionRates, typeRestrictions
├── Models/
│   ├── Reservation.php                 (35 líneas) – Agregados tax_amount, tax_rate, commission_amount al fillable y al total
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (82 líneas) – Agregada validación de tipo (event requiere mínimo 3 noches)
└── Controllers/
    └── ReservationController.php       (141 líneas) – Agregado cálculo de impuestos y comisión por tipo
```

**Total: 6 archivos, 398 líneas** (vs 6 archivos, 326 líneas en Phase 02)

**Crecimiento: +0 archivos, +72 líneas (+22%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Impuesto por tipo | Lookup en `PricingRules::taxRates()` usando el `product_type` del primer producto |
| Comisión por tipo | Lookup en `PricingRules::commissionRates()` solo para tipo "event" (3%) |
| Restricción de tipo | `withValidator` valida que "event" tenga mínimo 3 noches usando `PricingRules::typeRestrictions()` |
| Fórmula del total | `base_price - discount_amount + extras + tax_amount + commission_amount` |

---

## Puntos fuertes

- **Consistencia de payload:** No se añadió ningún campo nuevo al request. El tipo se deriva del catálogo existente (`product_type`).
- **Centralización de reglas:** `PricingRules` sigue siendo el único lugar donde se configuran los valores. Añadir un nuevo tipo de producto solo requiere actualizar el catálogo y las reglas.
- **Resultado correcto:** El test de equivalencia confirma que produce el mismo output que las otras 3 arquitecturas.
- **Validación temprana:** La restricción de noches mínimas para "event" se valida en el FormRequest antes de cualquier cálculo.

---

## Puntos débiles

- **Controlador de 141 líneas:** El método `store()` ahora maneja: precio base, extras, descuentos por volumen, promociones combinadas, precio mínimo, impuestos por tipo, comisión por tipo, y persistencia. Demasiadas responsabilidades en un solo método.
- **Lógica condicional por tipo inline:** El cálculo de `tax_amount` y `commission_amount` usa un lookup directo (`PricingRules::taxRates()[$primaryType]`). Si un tipo requiere lógica más compleja (ej. impuestos compuestos, comisiones escalonadas), el controlador crecerá aún más.
- **Tipo primario arbitrario:** Cuando hay múltiples productos de distinto tipo, se usa el primero (`reset($productTypes)`). No hay regla explícita para este caso, solo una decisión implícita en el código.
- **Sin abstracción de "tipo de reserva":** No existe un objeto que represente un "hotel" o un "event". Las diferencias se manejan con arrays de configuración y lookups directos.
- **Fórmula del total dispersa:** El cálculo del `total` attribute en el modelo incluye `tax_amount` y `commission_amount`, pero estos valores se calculan en el controlador. El modelo no tiene control sobre sus propios valores.
- **Hardcoding del formato de tax_rate:** `'Tax ' . intval($taxRate * 100) . '%'` es un formato string que no se valida ni se centraliza. Si cambia el formato, hay que buscar en el controlador.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Nuevas columnas (migración) | **Bajo** – 3 columnas, nullable/defaults |
| Cálculo de impuestos | **Bajo** – lookup directo en PricingRules |
| Cálculo de comisión | **Bajo** – condicional simple |
| Validación de tipo | **Bajo** – reutiliza el mismo patrón de withValidator de Phase 02 |
| Testeabilidad | **Alto** – todo acoplado al controlador, se necesitan tests de integración con BD |
| Añadir nuevo tipo de producto | **Medio** – requiere modificar ProductCatalog, PricingRules, y posiblemente la lógica del controlador si la fórmula es distinta |

El coste más alto es la **falta de encapsulamiento del tipo**: si mañana un "hotel" necesita una fórmula de impuesto compuesta o un "event" necesita comisiones escalonadas, toda esa lógica irá al controlador.

---

## Lecciones para Phase 04+

Phase 03 es el punto de quiebre del monolito: el controlador tiene 141 líneas y maneja 7 responsabilidades distintas. Cualquier cambio futuro (nuevos tipos, fórmulas complejas, reglas de combinación) requiere modificar un método que ya es difícil de leer y testear. Las otras arquitecturas (A02 Repository, A03 Strategy, A04 Decorator) resuelven este problema con abstracciones: Repository separa persistencia de lógica, Strategy encapsula fórmulas por tipo, y Decorator compone reglas incrementalmente. Este archivo es la evidencia de por qué el monolito no escala.
