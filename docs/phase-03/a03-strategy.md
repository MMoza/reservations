# Phase 03 – A03 Strategy + Polymorphism

## Resumen

Comportamiento polimórfico implementado extendiendo la interfaz `PricingStrategy` con métodos de cálculo de impuestos y comisión por tipo. `BasicPricingStrategy` implementa los nuevos métodos usando lookups en `PricingRules`. `MultiProductReservation::calculate()` ahora retorna `primary_type` además de `total_nights` y `product_ids`. El servicio orquesta todo calculando impuestos y comisión a través de la estrategia.

---

## Estructura

```
Phase_03/
├── Domain/
│   ├── Catalog/
│   │   ├── ProductCatalog.php            (80 líneas) – Agregado product_type + batch lookup
│   │   └── ProductCollection.php         (18 líneas) – Sin cambios
│   ├── Pricing/
│   │   ├── PricingStrategy.php           (18 líneas) – +4 métodos (calculateTax, calculateCommission, getTaxRate, getCommissionRate)
│   │   └── BasicPricingStrategy.php      (45 líneas) – Implementa métodos de impuesto y comisión
│   └── Reservations/
│       ├── Reservations.php              (20 líneas) – SIN USAR (persiste de Phase 01)
│       └── MultiProductReservation.php   (62 líneas) – Agrega primary_type al retorno
├── Catalog/
│   └── PricingRules.php                  (62 líneas) – Agregados taxRates, commissionRates, typeRestrictions
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (23 líneas) – +1 método updateTaxesAndCommission
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (54 líneas) – Implementación del nuevo método
├── Services/
│   └── CreateReservationService.php      (103 líneas) – Usa estrategia para calcular impuestos y comisión
├── Exceptions/
│   └── MinimumPriceException.php         (13 líneas) – Sin cambios
├── Models/
│   ├── Reservation.php                   (35 líneas) – Agregados tax_amount, tax_rate, commission_amount
│   └── Extra.php                         (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php       (82 líneas) – Agregada validación de tipo
└── Controllers/
    └── ReservationController.php         (32 líneas) – Sin cambios
```

**Total: 15 archivos, 727 líneas** (vs Phase 02: 15 archivos, 523 líneas)

**Crecimiento: +0 archivos, +204 líneas (+39%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Impuesto por tipo | `PricingStrategy::calculateTax($productType, $basePrice)` – encapsulado en la estrategia |
| Comisión por tipo | `PricingStrategy::calculateCommission($productType, $basePrice)` – encapsulado en la estrategia |
| Retorno de primary_type | `MultiProductReservation::calculate()` agrega `primary_type` al array de resultados |
| Restricción de tipo | `withValidator` valida que "event" tenga mínimo 3 noches |
| Persistencia separada | `updateTaxesAndCommission` en la interfaz del repositorio |

---

## Puntos fuertes

- **Strategy Pattern justificado:** Phase 03 es donde `PricingStrategy` demuestra su valor. Los métodos `calculateTax()` y `calculateCommission()` encapsulan la lógica de fórmulas por tipo. Si un nuevo tipo requiere una fórmula compleja, solo se necesita una nueva estrategia sin tocar el servicio.
- **Dominio enriquecido:** `MultiProductReservation::calculate()` retorna `primary_type`, haciendo que el dominio sea la fuente de verdad sobre el tipo de reserva. El servicio no tiene que inferirlo.
- **Controlador inmutable:** 32 líneas, sin cambios respecto a Phase 02.
- **Cálculo delegable:** A diferencia de A01/A02 donde los impuestos se calculan inline, en A03 la estrategia los calcula. Esto permite mockear la estrategia en tests y verificar cálculos aislados.
- **Interfaz explícita:** `PricingStrategy` declara claramente qué cálculos soporta. Es un contrato visible que documenta las capacidades del sistema.
- **Separación dominio/servicio:** El dominio calcula y retorna datos, el servicio orquesta, la estrategia encapsula fórmulas. Cada capa tiene una responsabilidad clara.

---

## Puntos débiles

- **Crecimiento más alto que A02:** +204 líneas vs +92 de A02. El Strategy Pattern añade más archivos y líneas para el mismo resultado funcional.
- **Estrategia instanciada directamente:** `new BasicPricingStrategy()` sigue hardcodeado en el servicio. No se inyecta, lo que limita la capacidad de cambiar la estrategia en runtime o en tests.
- **Clase `Reservations` sigue sin usarse:** La deuda de Phase 01 persiste. `MultiProductReservation` extiende de `Reservation` (Eloquent Model) en lugar de la abstracción de dominio.
- **Más archivos que A02:** 15 archivos y 727 líneas frente a 10 archivos y 503 líneas de A02. La ventaja del Strategy Pattern es arquitectónica, no en líneas de código.
- **Servicio aún orquestador pesado:** 103 líneas en `CreateReservationService`. Aunque calcula impuestos a través de la estrategia, sigue teniendo la responsabilidad de construir colección, crear estrategia, calcular, persistir, y aplicar reglas.
- **withValidator adaptado a API de batch:** Mismo problema que Phase 02: `ProductCatalog::findExtrasByIds([$extraId])` es incómodo para un solo elemento.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Extender PricingStrategy | **Bajo** – 4 métodos nuevos en la interfaz |
| Implementar en BasicPricingStrategy | **Bajo** – lookups directos en PricingRules |
| Enriquecer MultiProductReservation | **Bajo** – agregar primary_type al retorno |
| Extender repositorio | **Bajo** – 1 método nuevo |
| Orquestación en servicio | **Medio** – delegar cálculo a la estrategia |
| Validación de tipo | **Bajo** – reutiliza patrón de Phase 02 |
| Testeabilidad | **Medio** – dominio con herencia Eloquent dificulta tests unitarios puros |
| Añadir nuevo tipo de producto | **Bajo** – nueva estrategia sin tocar código existente |

El coste más alto fue **extender la interfaz `PricingStrategy`** de forma coherente: los nuevos métodos (`calculateTax`, `calculateCommission`, `getTaxRate`, `getCommissionRate`) deben seguir el contrato existente sin romper la implementación de `BasicPricingStrategy`.

---

## Lecciones para Phase 04+

A03 demuestra el valor del Strategy Pattern: las fórmulas por tipo están encapsuladas en la estrategia, no en el servicio. Si un nuevo tipo de producto requiere lógica compleja (impuestos compuestos, comisiones escalonadas), solo se necesita una nueva estrategia. Sin embargo, la herencia de Eloquent en `MultiProductReservation` y la clase abstracta sin usar son deudas que limitan la pureza del dominio. A04 (Decorator) podría complementar A03: Strategy para las fórmulas por tipo, Decorator para la composición incremental de reglas.
