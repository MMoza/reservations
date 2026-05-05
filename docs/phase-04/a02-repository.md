# Phase 04 – A02 Repository Pattern

## Resumen

Early booking discount (30+ días = 5%, 60+ días = 10%) y seasonal surcharge (julio/agosto = +15%) se implementan inline en el servicio. Se añaden `earlyBookingDiscounts()` y `seasonalSurcharge()` a `PricingRules`. El repositorio se extiende con `updateEarlyBookingAndSurcharge()`. El controlador permanece intacto (30 líneas). El servicio alcanza 172 líneas, acumulando todas las reglas desde Phase 01.

---

## Estructura

```
Phase_04/
├── Catalog/
│   ├── ProductCatalog.php              (58 líneas) – Sin cambios
│   └── PricingRules.php                (78 líneas) – Agregados earlyBookingDiscounts, seasonalSurcharge
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (29 líneas) – +1 método updateEarlyBookingAndSurcharge
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (65 líneas) – Implementación del nuevo método
├── Services/
│   └── ReservationService.php          (172 líneas) – TODA la lógica acumulada
├── Exceptions/
│   └── MinimumPriceException.php       (13 líneas) – Sin cambios
├── Models/
│   ├── Reservation.php                 (39 líneas) – Agregados early_booking_discount_amount y seasonal_surcharge_amount
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (82 líneas) – Sin cambios
└── Controllers/
    └── ReservationController.php       (30 líneas) – Sin cambios
```

**Total: 10 archivos, 587 líneas** (vs Phase 03: 10 archivos, 503 líneas)

**Crecimiento: +0 archivos, +84 líneas (+17%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Early booking 30 días | Si la primera fecha está 30+ días en el futuro, 5% de descuento sobre base_price |
| Early booking 60 días | Si la primera fecha está 60+ días en el futuro, 10% de descuento sobre base_price |
| Seasonal surcharge | Si alguna fecha cae en julio/agosto, 15% de recargo sobre base_price |
| Fórmula del total | `base_price - discounts - early_booking + seasonal_surcharge + extras + tax + commission` |

---

## Puntos fuertes

- **Controlador inmutable:** 30 líneas, sin cambios desde Phase 02. La separación controlador/servicio sigue protegiendo al controlador de la complejidad creciente.
- **Configuración centralizada:** `PricingRules` centraliza los umbrales de early booking y los meses de temporada alta. Cambiar valores no requiere tocar el servicio.
- **Servicio testable:** Toda la lógica vive en el servicio, que se puede testear con un repositorio mock. Los 5 tests funcionales confirman el comportamiento correcto.
- **Acumulación coherente:** Se sigue el mismo patrón de Phase 02 y 03: reglas inline en el servicio, configuración en PricingRules. No hay sorpresas para un desarrollador que ya conoce el código.
- **Resultado correcto:** Los tests de equivalencia confirman que produce el mismo output que las otras 3 arquitecturas.

---

## Puntos débiles

- **Servicio de 172 líneas:** El método `createReservation()` maneja: precio base, extras, descuentos por volumen, promociones combinadas, precio mínimo, impuestos por tipo, comisión por tipo, early booking discount, seasonal surcharge, y persistencia. Demasiadas responsabilidades en un solo método.
- **Acumulación de responsabilidades:** El servicio ahora hace:
  1. Crear la reserva vacía
  2. Calcular precio base de productos
  3. Calcular y persistir extras
  4. Calcular descuento por volumen
  5. Calcular descuento por promoción combinada
  6. Calcular descuento por early booking
  7. Verificar precio mínimo
  8. Calcular recargo por temporada
  9. Calcular impuestos por tipo
  10. Calcular comisión por tipo
  11. Persistir base_price, discounts, taxes, commission
  12. Persistir early_booking y seasonal_surcharge
  13. Retornar la reserva con extras

- **Lógica de early booking inline:** El cálculo de `daysInAdvance` usa `now()->diffInDays()` directamente en el servicio. Si se necesita lógica más compleja (zona horaria, días laborables), el servicio crecerá aún más.
- **Seasonal surcharge con break:** El bucle sobre `$allDates` hace `break` al encontrar la primera fecha en high season. El surcharge se aplica una sola vez al 15% del base_price, no por noche. Esta decisión está implícita en el código.
- **Orden de cálculo implícito:** Las reglas se ejecutan en un orden específico pero no hay nada que lo documente o valide. Cambiar el orden de las líneas podría cambiar el resultado.
- **Servicio "Dios" consolidado:** A02 resolvió el problema del controlador hinchado de A01, pero creó un problema equivalente en el servicio. El patrón Repository separa persistencia de lógica, pero no resuelve la complejidad de la lógica en sí.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Nuevas reglas en PricingRules | **Bajo** – métodos estáticos simples |
| Cálculo de early booking | **Bajo** – inline en el servicio |
| Cálculo de seasonal surcharge | **Bajo** – inline en el servicio |
| Extender interfaz repositorio | **Bajo** – 1 método nuevo |
| Nuevas columnas (migración) | **Bajo** – 2 columnas con defaults |
| Testeabilidad | **Medio** – servicio mockeable pero lógica procedural |
| Añadir Phase 05 | **Muy alto** – el servicio ya tiene 172 líneas y 13 responsabilidades |

El coste más alto es la **acumulación de responsabilidades en el servicio**: cada nueva regla se añade al mismo método `createReservation`. La ventaja respecto a A01 es que esta lógica está aislada del controlador y el servicio es testeable con mocks.

---

## Lecciones para Phase 05+

Phase 04 consolida la tesis de A02: el Repository Pattern resuelve el acoplamiento infraestructura/lógica pero no la complejidad algorítmica. El servicio de 172 líneas demuestra que separar en capas (controller → service → repository) no es suficiente cuando el dominio tiene muchas reglas combinatorias. Se necesita una abstracción adicional para las reglas (Strategy en A03) o para la composición incremental (Decorator en A04). A02 alcanza su límite arquitectónico aquí.
