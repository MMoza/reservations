# Phase 04 – A01 Monolithic Eloquent

## Resumen

Acumulación de más reglas de negocio directamente en el controlador, sin ninguna extracción. Se añaden early booking discount (reservar con 30+ o 60+ días de antelación = 5% o 10%) y seasonal surcharge (temporada alta julio/agosto = +15%). El controlador pasa de 141 a 184 líneas. Se mantienen 2 nuevas columnas en la tabla `reservations` (`early_booking_discount_amount`, `seasonal_surcharge_amount`).

---

## Estructura

```
Phase_04/
├── Catalog/
│   ├── ProductCatalog.php              (58 líneas) – Sin cambios
│   └── PricingRules.php                (83 líneas) – Agregados earlyBookingDiscounts, seasonalSurcharge, seasonalDiscountStacking
├── Models/
│   ├── Reservation.php                 (39 líneas) – Agregados early_booking_discount_amount y seasonal_surcharge_amount al fillable y al total
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (82 líneas) – Sin cambios
└── Controllers/
    └── ReservationController.php       (184 líneas) – TODA la lógica nueva inline
```

**Total: 6 archivos, 466 líneas** (vs Phase 03: 6 archivos, 398 líneas)

**Crecimiento: +0 archivos, +68 líneas (+17%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Early booking 30 días | Si la primera fecha de reserva está 30+ días en el futuro, 5% de descuento sobre base_price |
| Early booking 60 días | Si la primera fecha está 60+ días en el futuro, 10% de descuento sobre base_price |
| Seasonal surcharge | Si alguna fecha cae en julio o agosto, 15% de recargo sobre base_price |
| Fórmula del total | `base_price - discounts + extras + tax + commission - early_booking + seasonal_surcharge` |

---

## Puntos fuertes

- **Consistencia de patrón:** Se sigue el mismo patrón que Phase 02 y 03: toda la lógica en el controlador, configuración en `PricingRules`. No hay sorpresas para un desarrollador que ya conoce el código.
- **Rápido de implementar:** Las 2 nuevas reglas se añadieron en una sola sesión sin crear nuevas capas ni patrones.
- **Configuración centralizada:** `PricingRules` sigue siendo el único lugar donde se configuran los valores. Cambiar los meses de temporada alta o los umbrales de early booking no requiere tocar el controlador.
- **Resultado correcto:** Los 6 tests funcionales confirman el comportamiento correcto.

---

## Puntos débiles

- **Controlador de 184 líneas:** El método `store()` ahora maneja: precio base, extras, descuentos por volumen, promociones combinadas, precio mínimo, early booking discount, seasonal surcharge, impuestos por tipo, comisión por tipo, y persistencia. Demasiadas responsabilidades en un solo método.

- **Lógica de early booking inline:** El cálculo de `daysInAdvance` usa `now()->diffInDays()` directamente en el controlador. Si mañana se necesita una lógica más compleja (ej: considerar zona horaria del hotel, o días laborables), el controlador crecerá aún más.

- **Formato de discount_reason frágil:** `discount_reason` es un string concatenado (`'volume-10% + early-booking-5%'`). El cálculo de `early_booking_discount_amount` se hace parseando este string (`str_starts_with`, `explode`), lo cual es frágil y propenso a errores. Si el formato cambia, el cálculo se rompe silenciosamente.

- **Seasonal surcharge calculado con break:** El bucle sobre `$allDates` para detectar temporada alta hace `break` al encontrar la primera fecha en high season. Esto significa que el surcharge se aplica una sola vez al 15% del base_price, no por noche en temporada alta. Esta decisión de negocio está implícita en el código, no documentada.

- **Orden de cálculo implícito:** Las reglas se ejecutan en un orden específico (base → extras → volume → combined → early booking → minimum price → seasonal → tax → commission), pero no hay nada en el código que documente o valide este orden. Cambiar el orden de las líneas podría cambiar el resultado.

- **Acumulación de responsabilidades:** El controlador ahora hace:
  1. Validar el request
  2. Calcular precio base
  3. Calcular extras
  4. Calcular descuento por volumen
  5. Calcular descuento por promoción combinada
  6. Calcular descuento por early booking
  7. Verificar precio mínimo
  8. Calcular recargo por temporada
  9. Calcular impuestos por tipo
  10. Calcular comisión por tipo
  11. Extraer early booking discount del discount_reason (parsing de string)
  12. Persistir la reserva y los extras
  13. Retornar la respuesta

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Nuevas reglas en PricingRules | **Bajo** – métodos estáticos simples |
| Cálculo de early booking | **Bajo** – inline en el controlador |
| Cálculo de seasonal surcharge | **Bajo** – inline en el controlador |
| Nuevas columnas (migración) | **Bajo** – 2 columnas con defaults |
| Parsing de discount_reason | **Medio** – código frágil que se rompe si el formato cambia |
| Testeabilidad | **Alto** – sin abstracciones, se necesitan tests de integración con BD |
| Añadir Phase 05 | **Muy alto** – el controlador ya tiene 184 líneas y 13 responsabilidades |

El coste más alto es la **fragilidad del parsing de discount_reason**: extraer `early_booking_discount_amount` del string `discount_reason` es un hack necesario porque no hay un objeto que represente los descuentos individualmente.

---

## Lecciones para Phase 05+

Phase 04 es el punto donde el monolito demuestra su patrón real: se puede seguir añadiendo complejidad indefinidamente, pero cada regla nueva hace el código más difícil de leer, testear y mantener. El controlador tiene 184 líneas y 13 responsabilidades. Cualquier cambio futuro requiere leer y entender todas las reglas existentes para no romper nada. Las otras arquitecturas (A02 con Rule Engine, A03 con Strategy Injection, A04 con Builder + Rules) resuelven este problema con abstracciones que aíslan cada regla. Este archivo es la evidencia de por qué el monolito no escala más allá de cierta complejidad.
