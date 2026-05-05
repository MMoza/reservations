# Phase 04 – A04 Decorator Domain

## Resumen

Phase 04 en A04 introduce early booking discount y seasonal surcharge extendiendo el patrón Decorator con 2 nuevos decoradores (`EarlyBookingDecorator`, `SeasonalSurchargeDecorator`) y extrayendo la composición en un `ReservationPriceBuilder`. El Service se mantiene como orquestador delgado (~100 líneas). Se añaden `originalBasePrice()` al componente para que los cálculos porcentuales se basen siempre en el precio original y no en precios ya descontados.

---

## Estructura

```
Phase_04/
├── Catalog/
│   └── PricingRules.php                (78 líneas) – Agregados earlyBookingDiscounts, seasonalSurcharge, typeRestrictions, commissionRates
├── Models/
│   ├── Reservation.php                 (39 líneas) – Agregados early_booking_discount_amount y seasonal_surcharge_amount al fillable y al total
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (82 líneas) – Sin cambios
├── Exceptions/
│   └── MinimumPriceException.php       (16 líneas) – Sin cambios
├── Controllers/
│   └── ReservationController.php       (32 líneas) – Sin cambios
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php  (18 líneas) – Agregado updateEarlyBookingAndSurcharge()
│   └── Eloquent/
│       └── EloquentReservationRepository.php   (37 líneas) – Implementa updateEarlyBookingAndSurcharge()
├── Services/
│   └── CreateReservationService.php    (99 líneas) – Orquestador delgado, delega en Builder
└── Domain/
    ├── Catalog/
    │   └── ProductCatalog.php          (58 líneas) – Sin cambios
    └── Pricing/
        ├── ReservationComponent.php    (32 líneas) – Agregados originalBasePrice(), earlyBookingDiscountAmount(), seasonalSurchargeAmount(), allDates()
        ├── BaseReservation.php         (71 líneas) – Implementa nuevos métodos con valores por defecto
        ├── ReservationDecorator.php    (85 líneas) – Delega nuevos métodos al componente envuelto
        ├── ReservationPriceBuilder.php (152 líneas) – NUEVO: orquesta toda la composición de decoradores
        └── Decorators/
            ├── ProductBasePriceDecorator.php   (38 líneas) – Trackea dates y originalBasePrice
            ├── ExtraChargeDecorator.php        (28 líneas) – Sin cambios
            ├── VolumeDiscountDecorator.php     (35 líneas) – Usa originalBasePrice() para cálculos
            ├── CombinedPromoDecorator.php      (35 líneas) – Usa originalBasePrice() para cálculos
            ├── TaxDecorator.php                (34 líneas) – Usa originalBasePrice() para cálculos
            ├── CommissionDecorator.php         (28 líneas) – Usa originalBasePrice() para cálculos
            ├── EarlyBookingDecorator.php       (70 líneas) – NUEVO: calcula descuento por antelación
            └── SeasonalSurchargeDecorator.php  (52 líneas) – NUEVO: calcula recargo por temporada
```

**Total: 21 archivos, 1139 líneas** (vs Phase 03: 19 archivos, 878 líneas)

**Crecimiento: +2 archivos, +261 líneas (+30%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Early booking 30 días | `EarlyBookingDecorator` calcula 5% sobre `originalBasePrice()` si la primera fecha está 30+ días en el futuro |
| Early booking 60 días | `EarlyBookingDecorator` calcula 10% sobre `originalBasePrice()` si la primera fecha está 60+ días en el futuro |
| Seasonal surcharge | `SeasonalSurchargeDecorator` aplica 15% sobre `originalBasePrice()` si alguna fecha cae en julio/agosto |
| Fórmula del total | `originalBasePrice - discounts - early_booking + seasonal_surcharge + extras + tax + commission` |

---

## ReservationPriceBuilder

El `ReservationPriceBuilder` (152 líneas) es la pieza clave que mantiene el Service delgado. Encapsula toda la lógica de composición de decoradores:

```php
public function build(): ReservationComponent {
    $reservation = new BaseReservation();
    $reservation = $this->applyProducts($reservation);
    $reservation = $this->applyExtras($reservation);
    $reservation = $this->applyVolumeDiscount($reservation);
    $reservation = $this->applyCombinedPromotions($reservation);
    $reservation = $this->applyPhase04Rules($reservation);
    $this->validateMinimumPrice($reservation);
    return $reservation;
}
```

Cada método privado aplica un grupo de decoradores en el orden correcto. El orden de aplicación es explícito y visible en `build()`.

---

## originalBasePrice()

Phase 04 introduce `originalBasePrice()` en `ReservationComponent` para resolver un problema de diseño del patrón Decorator: los decoradores de descuento (VolumeDiscount, CombinedPromo, EarlyBooking) calculan sus porcentajes sobre `basePrice()`, pero `basePrice()` ya incluye los descuentos acumulados. Esto generaba cálculos incorrectos.

La solución es trackear el precio bruto original en paralelo:

| Método | Phase 03 | Phase 04 |
|---|---|---|
| `basePrice()` | Precio bruto (acumulado por ProductBasePrice) | Precio neto (bruto - descuentos) |
| `originalBasePrice()` | No existía | Precio bruto sin descontar |
| `discountAmount()` | Descuento acumulado | Descuento acumulado |

Todos los decoradores de porcentaje ahora usan `originalBasePrice()`:

```php
// VolumeDiscountDecorator
public function ownDiscountAmount(): float {
    return $this->reservation->originalBasePrice() * $this->discountPercentage;
}
```

---

## Puntos fuertes

- **Service orquestador delgado:** `CreateReservationService` tiene 99 líneas. Solo itera productos, alimenta el Builder y persiste. La lógica de composición está completamente encapsulada.
- **Orden de aplicación explícito:** `ReservationPriceBuilder::build()` muestra el orden de decoradores en 6 líneas. Cambiar el orden es trivial y visible.
- **Cada regla en su propio archivo:** `EarlyBookingDecorator` (70 líneas) y `SeasonalSurchargeDecorator` (52 líneas) son independientes. Añadir una regla nueva implica crear un nuevo decorador, no modificar código existente.
- **originalBasePrice() elimina bugs de cálculo:** Todos los porcentuales se calculan sobre el mismo valor base, independientemente del orden de aplicación.
- **Validación encapsulada:** El check de minimum price está en `ReservationPriceBuilder::validateMinimumPrice()`, no disperso en el Service.
- **Resultado correcto:** Los 5 tests funcionales confirman el comportamiento correcto.

---

## Puntos débiles

- **Archivos por regla:** Cada nueva regla de pricing requiere un nuevo archivo Decorator (interfaz + implementación + tests). Para reglas simples como early booking (70 líneas), puede sentirse excesivo comparado con una línea en el controlador de A01.
- **originalBasePrice() requiere disciplina:** Todos los decoradores nuevos deben usar `originalBasePrice()` en vez de `basePrice()` para cálculos porcentuales. Si un desarrollador se equivoca, los cálculos serán incorrectos sin error visible.
- **ReservationPriceBuilder conoce el orden:** El Builder codifica el orden de aplicación. Si Phase 05 necesita que un decorador se aplique en otro punto del pipeline, habrá que refactorizar `build()`.
- **Seasonal surcharge aplica una vez:** Al igual que en A01, el surcharge se aplica una sola vez al encontrar la primera fecha en high season (15% del base_price). Esta decisión está en `SeasonalSurchargeDecorator::applySeasonalSurcharge()` con un `break`, pero no es evidente sin leer el código.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Nuevos decoradores (EarlyBooking, SeasonalSurcharge) | **Medio** – 2 archivos nuevos con patrón consistente |
| ReservationPriceBuilder | **Medio** – 152 líneas pero bien organizado |
| Extender ReservationComponent con originalBasePrice() | **Bajo** – cambio mecánico en interface + todas las implementaciones |
| Nuevas columnas (migración) | **Bajo** – 2 columnas con defaults |
| Testeabilidad | **Bajo** – cada decorador se puede testear en aislamiento |
| Añadir Phase 05 | **Bajo** – crear nuevo Decorator, añadir al Builder, 0 cambios en Service |

El coste más bajo es la **extensibilidad**: añadir una nueva regla de pricing en A04 implica crear un decorador nuevo (siguiendo el patrón existente) y añadir una línea en `ReservationPriceBuilder::applyPhase04Rules()`. El Service no se toca.

---

## Comparación con A01 Phase 04

| Métrica | A01 (Monolito) | A04 (Decorator + Builder) |
|---|---|---|
| Tamaño del Service/Controller | 184 líneas | 99 líneas |
| Archivos totales | 6 | 21 |
| Líneas totales | 466 | 1139 |
| Dónde añadir regla nueva | Controller (modifica código existente) | Nuevo Decorator (añade archivo) |
| Orden de reglas implícito | Sí (orden de líneas en controller) | Sí (orden en Builder::build()) |
| Fragilidad de discount_reason | Alta (parsing de string) | Nula (cada decorador tiene su razón) |
| Testeabilidad de reglas individuales | Requiere test de integración | Se puede testear cada Decorator aisladamente |

A04 invierte más archivos iniciales (21 vs 6) pero gana en mantenibilidad: cada regla está aislada, el Service no crece, y los tests unitarios de decoradores son triviales.

---

## Lecciones para Phase 05+

Phase 04 en A04 demuestra que el patrón Builder + Decorator escala horizontalmente: añadir reglas nuevas no hace crecer el Service, solo añade Decorators al Builder. El coste de crear un nuevo archivo por regla se justifica por la capacidad de testear cada regla en aislamiento y por la legibilidad del Service orquestador. La introducción de `originalBasePrice()` fue una corrección de diseño necesaria que evita bugs sutiles en cálculos porcentuales acumulados. Para Phase 05, el camino es claro: nuevo Decorator → añadir al Builder → test unitario.
