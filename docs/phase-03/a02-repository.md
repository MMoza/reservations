# Phase 03 – A02 Repository Pattern

## Resumen

Comportamiento polimórfico implementado en el servicio. Se añade `updateTaxesAndCommission` a la interfaz del repositorio para persistir los nuevos campos. El servicio calcula impuestos y comisión usando `PricingRules::taxRates()` y `PricingRules::commissionRates()` basándose en el `product_type` del primer producto. El controlador sigue intacto (30 líneas, solo try/catch).

---

## Estructura

```
Phase_03/
├── Catalog/
│   ├── ProductCatalog.php              (58 líneas) – Agregado product_type
│   └── PricingRules.php                (62 líneas) – Agregados taxRates, commissionRates, typeRestrictions
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (23 líneas) – +1 método updateTaxesAndCommission
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (54 líneas) – Implementación del nuevo método
├── Services/
│   └── ReservationService.php          (126 líneas) – Agregado cálculo de impuestos y comisión
├── Exceptions/
│   └── MinimumPriceException.php       (13 líneas) – Sin cambios
├── Models/
│   ├── Reservation.php                 (35 líneas) – Agregados tax_amount, tax_rate, commission_amount
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (82 líneas) – Agregada validación de tipo
└── Controllers/
    └── ReservationController.php       (30 líneas) – Sin cambios
```

**Total: 10 archivos, 503 líneas** (vs Phase 02: 10 archivos, 411 líneas)

**Crecimiento: +0 archivos, +92 líneas (+22%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Impuesto por tipo | Lookup en `PricingRules::taxRates()` usando el `product_type` del primer producto |
| Comisión por tipo | Lookup en `PricingRules::commissionRates()` solo para tipo "event" (3%) |
| Persistencia separada | Nuevo método `updateTaxesAndCommission` en la interfaz del repositorio |
| Restricción de tipo | `withValidator` valida que "event" tenga mínimo 3 noches |
| Fórmula del total | `base_price - discount_amount + extras + tax_amount + commission_amount` |

---

## Puntos fuertes

- **Controlador inmutable:** 30 líneas, sin cambios respecto a Phase 02. La separación controlador/servicio protege al controlador de la complejidad creciente.
- **Repositorio extendido limpiamente:** `updateTaxesAndCommission` se añade a la interfaz y a la implementación Eloquent como un método atómico. Cada capa de persistencia tiene su propia responsabilidad.
- **Servicio testable:** Los impuestos y la comisión se calculan en el servicio, que se puede testear con un repositorio mock. No hay acoplamiento a Eloquent.
- **Excepción consistente:** `MinimumPriceException` sigue siendo el mecanismo de error para el precio mínimo. Los impuestos y comisión no lanzan excepciones (siempre tienen un valor por defecto de 0).
- **Separación de responsabilidades:** El repositorio persiste, el servicio calcula, el controlador coordina. Cada clase tiene una razón clara para cambiar.

---

## Puntos débiles

- **Servicio de 126 líneas:** Aunque mejor que el controlador de 141 líneas de A01, sigue siendo una clase con demasiadas responsabilidades: cálculo de precio base, extras, descuentos, precio mínimo, impuestos, comisión, y persistencia.
- **Lógica procedural en el servicio:** Los impuestos y la comisión se calculan con lookups directos y condicionales inline. No hay objetos que representen "impuesto" o "comisión".
- **Tipo primario arbitrario:** Cuando hay múltiples productos de distinto tipo, se usa el primero (`reset($productTypes)`). No hay regla explícita para este caso.
- **Servicio conoce todo:** Depende de `ProductCatalog`, `PricingRules`, `ReservationRepositoryInterface`, y `MinimumPriceException`. Demasiadas dependencias en una sola clase.
- **Repositorio sigue siendo un wrapper:** `EloquentReservationRepository` sigue siendo un CRUD directo sobre Eloquent. No aporta validación ni lógica de dominio.
- **Formato de tax_rate como string:** `'Tax ' . intval($taxRate * 100) . '%'` está hardcodeado en el servicio. Si cambia el formato, hay que buscar en el servicio.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Extender interfaz repositorio | **Bajo** – 1 método nuevo |
| Implementación Eloquent | **Bajo** – update directo |
| Cálculo de impuestos en servicio | **Bajo** – lookup en PricingRules |
| Cálculo de comisión en servicio | **Bajo** – condicional simple |
| Validación de tipo | **Bajo** – reutiliza patrón de Phase 02 |
| Testeabilidad | **Medio** – servicio mockeable pero lógica procedural |
| Añadir nuevo tipo de producto | **Medio** – requiere modificar PricingRules y posiblemente la lógica del servicio |

El coste más alto es la **acumulación de responsabilidades en el servicio**: cada nueva regla (descuentos, impuestos, comisión) se añade al mismo método `createReservation`. La ventaja respecto a A01 es que esta lógica está aislada del controlador.

---

## Lecciones para Phase 04+

A02 resuelve el problema del controlador hinchado de A01, pero crea un problema similar en el servicio. La separación controlador/servicio/repositorio es clara, pero el servicio sigue siendo una clase "Dios" que orquesta todo. Phase 03 demuestra que el patrón Repository no es suficiente para manejar comportamiento polimórfico complejo: se necesita una abstracción adicional para las reglas de cálculo (Strategy en A03) o para la composición incremental (Decorator en A04).
