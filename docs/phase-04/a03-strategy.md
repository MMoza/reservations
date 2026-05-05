# Phase 04 – A03 Strategy + Polymorphism

## Resumen

Early booking discount y seasonal surcharge se implementan extendiendo `PricingStrategy` con `calculateEarlyBookingDiscount()`, `getEarlyBookingRate()` y `calculateSeasonalSurcharge()`. Se crea `Phase04PricingStrategy` (nueva implementación) para no romper `BasicPricingStrategy`. `MultiProductReservation::calculate()` retorna `all_dates` adicionalmente. El servicio alcanza 123 líneas.

---

## Estructura

```
Phase_04/
├── Domain/
│   ├── Catalog/
│   │   ├── ProductCatalog.php            (80 líneas) – Sin cambios
│   │   └── ProductCollection.php         (18 líneas) – Sin cambios
│   ├── Pricing/
│   │   ├── PricingStrategy.php           (22 líneas) – +3 métodos (early booking, seasonal surcharge)
│   │   └── Phase04PricingStrategy.php    (71 líneas) – NUEVO: implementa métodos Phase 04
│   └── Reservations/
│       ├── Reservations.php              (20 líneas) – SIN USAR (persiste de Phase 01)
│       └── MultiProductReservation.php   (62 líneas) – Agrega all_dates al retorno
├── Catalog/
│   └── PricingRules.php                  (78 líneas) – Agregados earlyBookingDiscounts, seasonalSurcharge
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (29 líneas) – +1 método updateEarlyBookingAndSurcharge
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (65 líneas) – Implementación del nuevo método
├── Services/
│   └── CreateReservationService.php      (123 líneas) – Usa estrategia para calcular Phase 04
├── Exceptions/
│   └── MinimumPriceException.php         (13 líneas) – Sin cambios
├── Models/
│   ├── Reservation.php                   (39 líneas) – Agregados early_booking_discount_amount y seasonal_surcharge_amount
│   └── Extra.php                         (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php       (82 líneas) – Sin cambios
└── Controllers/
    └── ReservationController.php         (32 líneas) – Sin cambios
```

**Total: 14 archivos, 775 líneas** (vs Phase 03: 15 archivos, 727 líneas)

**Crecimiento: -1 archivo (PricingRules ya no duplicado en Domain/Catalog), +48 líneas (+7%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Early booking 30 días | `Phase04PricingStrategy::calculateEarlyBookingDiscount(basePrice, daysInAdvance)` – 5% sobre base_price |
| Early booking 60 días | `Phase04PricingStrategy::calculateEarlyBookingDiscount(basePrice, daysInAdvance)` – 10% sobre base_price |
| Seasonal surcharge | `Phase04PricingStrategy::calculateSeasonalSurcharge(basePrice, dates)` – 15% si hay fecha en julio/agosto |
| Fórmula del total | `base_price - discounts - early_booking + seasonal_surcharge + extras + tax + commission` |

---

## Puntos fuertes

- **Strategy Pattern extendido coherentemente:** Los nuevos métodos (`calculateEarlyBookingDiscount`, `calculateSeasonalSurcharge`) se añaden a la interfaz `PricingStrategy` y se implementan en `Phase04PricingStrategy`. Esto permite cambiar las fórmulas sin tocar el servicio.
- **Cálculo delegable:** A diferencia de A01/A02 donde early booking se calcula inline, en A03 la estrategia lo calcula. Esto permite mockear la estrategia en tests y verificar cálculos aislados.
- **Servicio moderado:** 123 líneas es significativamente menos que las 172 de A02 y las 184 de A01. La estrategia absorbe parte de la complejidad.
- **Controlador inmutable:** 32 líneas, sin cambios desde Phase 02.
- **Dominio enriquecido:** `MultiProductReservation::calculate()` retorna `all_dates`, haciendo que el dominio sea la fuente de verdad sobre las fechas de la reserva.
- **Resultado correcto:** Los 5 tests funcionales confirman el comportamiento correcto.

---

## Puntos débiles

- **Phase04PricingStrategy vs BasicPricingStrategy:** Se crea una nueva implementación en lugar de extender la existente. Esto genera duplicación: `Phase04PricingStrategy` repite toda la lógica de `BasicPricingStrategy` (calculateProduct, calculateExtra, calculateTax, calculateCommission) más los nuevos métodos. Sería mejor usar composición o herencia.
- **Estrategia instanciada directamente:** `new Phase04PricingStrategy()` sigue hardcodeado en el servicio. No se inyecta, lo que limita la capacidad de cambiar la estrategia en runtime o en tests.
- **Clase `Reservations` sigue sin usarse:** La deuda de Phase 01 persiste. `MultiProductReservation` extiende de `Reservation` (Eloquent Model) en lugar de la abstracción de dominio.
- **More files than A02 for same result:** 14 archivos y 775 líneas frente a 10 archivos y 587 líneas de A02. La ventaja del Strategy Pattern es arquitectónica, no en líneas de código.
- **Servicio aún orquestador:** 123 líneas en `CreateReservationService`. Aunque delega el cálculo de Phase 04 a la estrategia, sigue teniendo la responsabilidad de construir colección, crear estrategia, calcular, aplicar reglas, persistir, y retornar.
- **withValidator adaptado a API de batch:** Mismo problema que fases anteriores: `ProductCatalog::findExtrasByIds([$extraId])` es incómodo para un solo elemento.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Extender PricingStrategy | **Bajo** – 3 métodos nuevos en la interfaz |
| Crear Phase04PricingStrategy | **Medio** – duplica BasicPricingStrategy + añade nuevos métodos |
| Enriquecer MultiProductReservation | **Bajo** – agregar all_dates al retorno |
| Extender repositorio | **Bajo** – 1 método nuevo |
| Orquestación en servicio | **Medio** – delegar cálculo a la estrategia |
| Testeabilidad | **Medio** – dominio con herencia Eloquent dificulta tests unitarios puros |
| Añadir nuevo tipo de cálculo | **Bajo** – nueva estrategia sin tocar código existente |

El coste más alto fue **crear Phase04PricingStrategy**: duplicar toda la lógica de BasicPricingStrategy para añadir 3 métodos nuevos es una decisión de diseño cuestionable. Una alternativa sería que `BasicPricingStrategy` tuviera los métodos nuevos directamente, o usar un patrón Decorator sobre la estrategia.

---

## Lecciones para Phase 05+

A03 demuestra que el Strategy Pattern escala mejor que el enfoque procedural de A02 para cálculos: las fórmulas están encapsuladas, no inline. Sin embargo, la duplicación entre `BasicPricingStrategy` y `Phase04PricingStrategy` revela una limitación del patrón: cada nueva "versión" de la estrategia requiere una clase nueva que repite todo lo anterior. Para Phase 05, sería preferible refactorizar hacia un enfoque donde las estrategias se compongan (ej: `BasicPricingStrategy` + `EarlyBookingStrategy` como decoradores de estrategia) en lugar de crear `Phase05PricingStrategy` que duplica todo de nuevo.

---

## Deuda arquitectónica no resuelta

A lo largo de las 4 fases se han señalado dos deudas en A03 que nunca se resolvieron:

### 1. `Reservations.php` sin usar (código muerto)

La clase abstracta `Reservations` se creó en Phase 01 como base para polimorfismo (`SingleProductReservation`, `MultiProductReservation`, etc.) pero nunca se usó: `MultiProductReservation` extiende directamente de `Reservation` (Eloquent Model).

**Por qué no se eliminó:** La clase abstracta se mantiene como evidencia del diseño original y como placeholder para una posible refactorización futura. Eliminarla rompería la intención arquitectónica del patrón Strategy, incluso si no está implementada correctamente. En un proyecto real, se eliminaría o se usaría.

### 2. `MultiProductReservation` heredando de Eloquent

El objeto de dominio debería ser puro, pero hereda del Eloquent Model. Esto mezcla persistencia con lógica de cálculo.

**Por qué no se corrigió:** Resolver esto requería que `MultiProductReservation` dejara de ser un modelo Eloquent y se convirtiera en un objeto de dominio puro, lo que implicaría:
- Cambiar la firma de `calculate()` para que no retorne un array sino un objeto de valor
- Separar la persistencia (`Reservation::create()`) del cálculo de precio
- Adaptar el repositorio para aceptar un objeto de dominio en lugar de un array

Este cambio es sustancial y alteraría la naturaleza del experimento (comparar arquitecturas con el mismo dominio funcional). Se decidió mantener la inconsistencia para que las 4 fases de A03 sean comparables entre sí, documentando la deuda en cada paso.
