# Phase 03 – A04 Decorator Domain

## Resumen

Comportamiento polimórfico implementado como **dos nuevos decoradores** (`TaxDecorator` y `CommissionDecorator`) que se añaden a la cadena de pricing. `ProductBasePriceDecorator` se extiende para rastrear el `product_type` del producto, permitiendo que los nuevos decoradores calculen impuestos y comisión basándose en el tipo. La cadena se construye como: base → extras → volumen → promo → tax → commission.

---

## Estructura

```
Phase_03/
├── Domain/
│   ├── Catalog/
│   │   └── ProductCatalog.php            (58 líneas) – Agregado product_type
│   └── Pricing/
│       ├── ReservationComponent.php      (24 líneas) – +4 métodos (taxAmount, taxRate, commissionAmount, productType)
│       ├── BaseReservation.php           (51 líneas) – Implementa nuevos métodos con valores default
│       ├── ReservationDecorator.php      (63 líneas) – Delega nuevos métodos al decorador interior
│       ├── Decorators/
│       │   ├── ProductBasePriceDecorator.php  (27 líneas) – NUEVO: acepta product_type
│       │   ├── ExtraChargeDecorator.php       (28 líneas) – Sin cambios
│       │   ├── VolumeDiscountDecorator.php    (35 líneas) – Agrega ownDiscountAmount()
│       │   ├── CombinedPromoDecorator.php     (35 líneas) – Agrega ownDiscountAmount()
│       │   ├── TaxDecorator.php               (34 líneas) – NUEVO: calcula impuestos por tipo
│       │   └── CommissionDecorator.php        (28 líneas) – NUEVO: calcula comisión por tipo
├── Catalog/
│   └── PricingRules.php                  (62 líneas) – Agregados taxRates, commissionRates, typeRestrictions
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (23 líneas) – +1 método updateTaxesAndCommission
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (54 líneas) – Implementación del nuevo método
├── Services/
│   └── CreateReservationService.php      (174 líneas) – Agrega TaxDecorator y CommissionDecorator a la cadena
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

**Total: 19 archivos, 878 líneas** (vs Phase 02: 17 archivos, 671 líneas)

**Crecimiento: +2 archivos, +207 líneas (+31%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Impuesto por tipo | `TaxDecorator` calcula `basePrice × taxRate(productType)` |
| Comisión por tipo | `CommissionDecorator` calcula `basePrice × commissionRate(productType)` |
| Rastreo de tipo | `ProductBasePriceDecorator` almacena `product_type` para que los decoradores posteriores lo usen |
| Restricción de tipo | `withValidator` valida que "event" tenga mínimo 3 noches |
| Persistencia separada | `updateTaxesAndCommission` en la interfaz del repositorio |

---

## Puntos fuertes

- **Impuestos y comisión como decoradores:** `TaxDecorator` y `CommissionDecorator` son clases con responsabilidad única. Cada una encapsula su propia lógica sin afectar al resto. Si un tipo requiere una fórmula de impuesto compleja, solo se modifica ese decorador.
- **Dominio puro sin Eloquent:** Todos los objetos de pricing (`ReservationComponent`, decoradores, `BaseReservation`) son PHP puro. No dependen de framework ni base de datos. Totalmente testeables de forma aislada.
- **Composición dinámica por tipo:** La cadena de decoradores se construye en runtime según los productos de la reserva. El `product_type` se propaga a través de `ProductBasePriceDecorator` y es accesible por cualquier decorador posterior.
- **Extensibilidad por composición:** Añadir un nuevo impuesto (ej: `SurchargeDecorator` para temporadas altas) requiere una nueva clase de ~30 líneas. No se modifica código existente.
- **Interfaz extendida coherentemente:** `taxAmount()`, `taxRate()`, `commissionAmount()`, `productType()` se añaden a `ReservationComponent`. `BaseReservation` retorna valores default (0/null), cada decorador los sobrescribe según su responsabilidad.
- **Servicio solo orquesta:** `CreateReservationService` añade `new TaxDecorator($reservationPrice)` y `new CommissionDecorator($reservationPrice)` a la cadena. La lógica de cálculo está en los decoradores, no en el servicio.
- **Resultado correcto:** El test de equivalencia confirma que produce el mismo output que las otras 3 arquitecturas.

---

## Puntos Débiles

- **Mayor complejidad total:** 19 archivos y 878 líneas, más del cuádruple que A01 (6 archivos, 398 líneas en Phase 03). Para el mismo resultado funcional, la inversión en abstracción es muy significativa.
- **Servicio orquestador crecido:** `CreateReservationService` tiene 174 líneas. La lógica de construir la cadena de decoradores en el orden correcto (base → extras → volumen → promo → tax → commission) es compleja de seguir.
- **Propagación implícita del tipo:** El `product_type` se establece en `ProductBasePriceDecorator` y se propaga a través de la cadena de decoradores. No hay un objeto que represente explícitamente "el contexto de la reserva con su tipo".
- **Curva de aprendizaje muy alta:** Un desarrollador nuevo necesita entender el patrón Decorator, la interfaz `ReservationComponent`, la clase base `BaseReservation`, `ReservationDecorator`, y cómo cada decorador envuelve al anterior. En A01, solo hay que leer un método `store()`.
- **Depuración compleja:** Si un impuesto es incorrecto, hay que desenrollar una cadena de 6-7 decoradores para entender dónde se calculó cada componente.
- **Código boilerplate muy significativo:** Cada decorador tiene ~30 líneas con un constructor y métodos que delegan al decorador interior. La mayor parte es código repetitivo.
- **Formato de tax_rate como string:** `'Tax ' . intval($rate * 100) . '%'` está hardcodeado en `TaxDecorator`. Si cambia el formato, hay que buscar en el decorador.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Extender ReservationComponent | **Bajo** – 4 métodos nuevos |
| Implementar en BaseReservation y ReservationDecorator | **Bajo** – delegación directa |
| Crear TaxDecorator | **Bajo** – ~34 líneas, patrón claro |
| Crear CommissionDecorator | **Bajo** – ~28 líneas, misma estructura |
| Extender ProductBasePriceDecorator | **Bajo** – agregar parámetro product_type |
| Orquestación en servicio | **Alto** – 174 líneas, construir cadena con orden correcto |
| Ajustar precio mínimo | **Medio** – verificación después de todos los decoradores |
| Testeabilidad | **Alto** – dominio puro, fácil de testear sin BD |
| Añadir nuevo tipo de producto | **Bajo** – nuevo decorador sin tocar código existente |

El coste más alto fue **la orquestación del servicio**: construir la cadena de decoradores en el orden correcto (base → extras → volumen → combinada → tax → commission) y asegurar que cada decorador recibe el `product_type` necesario.

---

## Lecciones para Phase 04+

A04 es la arquitectura que mejor encapsula las reglas de Phase 03: impuestos y comisión son objetos independientes que se componen sobre la cadena de precio existente. El patrón Decorator brilla aquí: cada regla es un objeto con responsabilidad única, testeable en aislamiento, y componible en cualquier orden. Sin embargo, el coste en complejidad y líneas de código es el más alto de las 4 arquitecturas. La pregunta para Phase 04 será: ¿justifica la extensibilidad del Decorator su coste de mantenimiento? Para un sistema con muchas reglas de pricing que cambian frecuentemente, la respuesta es probablemente sí. Para un sistema estable con pocas reglas, A02 o A03 podrían ser más eficientes.
