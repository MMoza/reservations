# Phase 02 – A04 Decorator Domain

## Resumen

Las reglas condicionales se implementan como **decoradores** que envuelven la cadena de precio existente. `VolumeDiscountDecorator` y `CombinedPromoDecorator` son nuevos decoradores que aplican descuentos porcentuales sobre el `basePrice()` del decorador anterior. Los descuentos se aplican **secuencialmente** (cada uno sobre el resultado del anterior), no aditivamente como en las otras arquitecturas.

---

## Estructura

```
Phase_02/
├── Domain/
│   ├── Catalog/
│   │   └── ProductCatalog.php            (50 líneas) – Catálogo estático
│   └── Pricing/
│       ├── ReservationComponent.php      (16 líneas) – Interfaz extendida (+2 métodos)
│       ├── BaseReservation.php           (31 líneas) – Implementa nuevos métodos
│       ├── ReservationDecorator.php      (40 líneas) – Delega nuevos métodos
│       ├── Decorators/
│       │   ├── ProductBasePriceDecorator.php  (19 líneas) – Copia
│       │   ├── ExtraChargeDecorator.php       (25 líneas) – Copia
│       │   ├── VolumeDiscountDecorator.php    (37 líneas) – NUEVO
│       │   └── CombinedPromoDecorator.php     (37 líneas) – NUEVO
├── Catalog/
│   └── PricingRules.php                  (38 líneas) – Configuración de reglas
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (16 líneas) – +2 métodos
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (40 líneas) – Implementaciones
├── Services/
│   └── CreateReservationService.php      (162 líneas) – Orquestación extendida
├── Exceptions/
│   └── MinimumPriceException.php         (13 líneas) – Nuevo
├── Models/
│   ├── Reservation.php                   (28 líneas) – Agregados discount_amount/reason
│   └── Extra.php                         (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php       (67 líneas) – Agregado withValidator
└── Controllers/
    └── ReservationController.php         (32 líneas) – try/catch simple
```

**Total: 17 archivos, 671 líneas** (vs Phase 01: 13 archivos, 423 líneas)

**Crecimiento: +4 archivos, +248 líneas (+59%)**

---

## Puntos fuertes

- **Descuentos como objetos de dominio:** `VolumeDiscountDecorator` y `CombinedPromoDecorator` son clases con responsabilidad única. Cada una encapsula su propia lógica de descuento sin afectar al resto. Esto es el patrón Decorator en su forma más pura.
- **Dominio puro sin Eloquent:** Los objetos de pricing (`ReservationComponent`, decoradores) son PHP puro. No dependen de framework ni base de datos. Totalmente testeables de forma aislada.
- **Composición dinámica:** La cadena de decoradores se construye en runtime según las condiciones de la reserva. Si no hay descuento por volumen, el decorator no se crea. Si hay promo combinada, se añade. Sin `if/else` de tipo de descuento dentro del precio.
- **Extensibilidad por composición:** Añadir un nuevo tipo de descuento (ej: `EarlyBookingDecorator`) requiere una nueva clase de ~30 líneas. No se modifica código existente. El servicio solo necesita una nueva condición para instanciarlo.
- **Interfaz extendida coherentemente:** `discountAmount()` y `discountReason()` se añaden a `ReservationComponent`, cada decorador los implementa acumulando desde el decorador anterior.
- **Resultado correcto:** Los tests de equivalencia confirman que produce el mismo output que las otras arquitecturas (aunque internamente el cálculo es diferente — secuencial vs aditivo).

---

## Puntos débiles

- **Mayor complejidad total:** 17 archivos y 671 líneas, casi el triple que A01 (5 archivos, 218 líneas en Phase 01). Para el mismo resultado funcional, la inversión en abstracción es significativa.
- **Servicio orquestador crecido:** `CreateReservationService` tiene 162 líneas (vs 118 en Phase 01). La lógica de construir la cadena de decoradores de precio base, añadir extras, y luego envolver con decoradores de descuento es compleja de seguir.
- **Descuentos secuenciales vs aditivos:** En A01/A02/A03, el volumen-20% y la promo-5% se aplican ambos sobre el precio original (1470 para 14 noches × 2 productos). En A04, el segundo decorador aplica sobre el resultado del primero (1862 → 1773.9). Esto produce **resultados numéricos diferentes** de las otras arquitecturas para el caso de descuentos combinados.
- **Curva de aprendizaje alta:** Un desarrollador nuevo necesita entender el patrón Decorator, la interfaz `ReservationComponent`, la clase base `ReservationDecorator`, y cómo cada decorador envuelve al anterior. En A01, solo hay que leer un método `store()`.
- **Depuración compleja:** Si un precio es incorrecto, hay que desenrollar una cadena de 4-5 decoradores (BaseReservation → ProductBasePriceDecorator × 2 → VolumeDiscountDecorator → CombinedPromoDecorator) para entender dónde se calculó cada componente.
- **Código boilerplate significativo:** Cada decorador tiene ~35 líneas con un constructor, `basePrice()`, `discountAmount()`, `discountReason()`. La mayor parte es delegación al decorador interior.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Extender interfaz ReservationComponent | **Bajo** – 2 métodos nuevos |
| Implementar en BaseReservation y ReservationDecorator | **Bajo** – delegación directa |
| Crear VolumeDiscountDecorator | **Bajo** – ~35 líneas, patrón claro |
| Crear CombinedPromoDecorator | **Bajo** – misma estructura que el anterior |
| Orquestación en servicio | **Alto** – 162 líneas, construir y envolver decoradores |
| Ajustar precios mínimos | **Medio** – la verificación debe ser después de todos los decoradores |
| Testeabilidad | **Alto** – dominio puro, fácil de testear sin BD |
| Añadir Phase 03 | **Medio** – los decoradores de descuento están separados del cálculo base, pero Phase 03 requerirá nuevos decorators por tipo de reserva |

El coste más alto fue **la orquestación del servicio**: construir la cadena de decoradores en el orden correcto (base → extras → volumen → combinada) y asegurar que cada decorador recibe la información necesaria.

---

## Lecciones para Phase 03

Phase 03 introducirá comportamiento polimórfico (tipos de reserva distintos con fórmulas diferentes). A04 está bien posicionada: el patrón Decorator permite añadir decoradores específicos por tipo de reserva. Sin embargo, el servicio orquestador ya tiene 162 líneas y crecerá aún más con la lógica de selección de tipo. La pregunta para Phase 03 será: ¿quién decide qué decoradores aplicar — el servicio o un objeto de dominio?
