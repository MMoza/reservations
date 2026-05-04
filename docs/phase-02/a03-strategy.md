# Phase 02 – A03 Strategy + Polymorphism

## Resumen

Las reglas condicionales se añaden al servicio, pero el dominio (`MultiProductReservation`) ahora calcula y devuelve `total_nights` y `product_ids` junto con el precio base y los extras. El servicio usa estos datos para aplicar descuentos y verificar el precio mínimo. `MinimumPriceException` maneja las violaciones del precio mínimo.

---

## Estructura

```
Phase_02/
├── Domain/
│   ├── Catalog/
│   │   ├── ProductCatalog.php            (52 líneas) – Catálogo con batch lookup
│   │   └── ProductCollection.php         (18 líneas) – Wrapper inmutable
│   ├── Pricing/
│   │   ├── PricingStrategy.php           (10 líneas) – Interfaz
│   │   └── BasicPricingStrategy.php      (24 líneas) – Implementación
│   └── Reservations/
│       ├── Reservations.php              (20 líneas) – Abstracta (SIGUE SIN USARSE)
│       └── MultiProductReservation.php   (57 líneas) – Calcula y retorna +datos
├── Catalog/
│   └── PricingRules.php                  (38 líneas) – Configuración de reglas
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (16 líneas) – +2 métodos
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (40 líneas) – Implementaciones
├── Services/
│   └── CreateReservationService.php      (88 líneas) – Orquestación con reglas
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

**Total: 15 archivos, 523 líneas** (vs Phase 01: 13 archivos, 371 líneas)

**Crecimiento: +2 archivos, +152 líneas (+41%)**

---

## Puntos fuertes

- **Dominio enriquecido:** `MultiProductReservation::calculate()` ahora retorna `total_nights` y `product_ids` además de `base_price` y `extras`. El dominio devuelve todo lo que el servicio necesita para aplicar las reglas condicionales. Esta es una mejora significativa respecto a Phase 01.
- **Strategy Pattern activo:** `BasicPricingStrategy` encapsula el cálculo de precio base y extras. Si Phase 03 requiere fórmulas diferentes por tipo de reserva, solo se necesita una nueva estrategia.
- **Batch lookup eficiente:** `ProductCatalog::findProductsByIds()` y `findExtrasByIds()` hacen una sola búsqueda en lugar de N queries individuales.
- **Controlador delgado:** 32 líneas con try/catch, igual que A02.
- **Separación dominio/servicio:** El dominio calcula, el servicio orquesta y aplica reglas. Cada capa tiene una responsabilidad clara.
- **Excepción como mecanismo de error:** `MinimumPriceException` igual que A02, separando la señalización de error del flujo normal.

---

## Puntos débiles

- **Clase `Reservations` sigue sin usarse:** La clase abstracta de Phase 01 sigue presente como código muerto. `MultiProductReservation` extiende de `Reservation` (Eloquent Model) en lugar de la abstracción de dominio. Esta inconsistencia persiste en Phase 02.
- **MultiProductReservation hereda de Eloquent:** A pesar de calcular precios como un objeto de dominio, sigue siendo un modelo Eloquent. La mezcla persistencia/dominio no se ha resuelto.
- **Estrategia instanciada directamente:** `new BasicPricingStrategy()` sigue hardcodeado en el servicio, no se inyecta. Esto limita la capacidad de cambiar la estrategia en runtime o en tests.
- **Más archivos que A02 para el mismo resultado:** 15 archivos y 523 líneas frente a 10 archivos y 411 líneas de A02. El Strategy Pattern añade complejidad (interfaz, implementación, colección) que no se justifica completamente con las reglas actuales.
- **Servicio aún orquestador pesado:** 88 líneas en `CreateReservationService`. Aunque mejor que las 106 de A02, sigue teniendo la responsabilidad de construir la colección, crear la estrategia, calcular, persistir, y aplicar reglas.
- **withValidator adaptado a API de batch:** `ProductCatalog::findExtrasByIds([$extraId])` es un lookup de un solo elemento usando un método diseñado para batch. Funciona pero es incómodo.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Enriquecer MultiProductReservation | **Bajo** – añadir 2 campos al array de retorno |
| Batch lookup en validación | **Bajo** – adaptar `findExtrasByIds` para un solo elemento |
| Lógica de descuentos en servicio | **Bajo** – mismo cálculo que A02, pero con datos del dominio |
| Interfaz repositorio | **Bajo** – 2 métodos nuevos |
| Código muerto (`Reservations` abstracta) | **Cero** – no se tocó, pero sigue ahí confundiendo |
| Testeabilidad | **Medio** – dominio con herencia Eloquent dificulta tests unitarios puros |
| Añadir Phase 03 | **Medio** – el Strategy Pattern ya está preparado, pero la herencia de Eloquent complica |

El coste más alto fue **enriquecer el dominio** para que retorne `total_nights` y `product_ids`, lo cual requirió modificar `MultiProductReservation::calculate()` sin romper la lógica existente de `base_price` y `extras`.

---

## Lecciones para Phase 03

Phase 03 introducirá comportamiento polimórfico (tipos de reserva distintos con fórmulas diferentes). A03 está parcialmente preparada: el patrón Strategy existe y `MultiProductReservation` ya retorna datos contextualizados. Pero la herencia de Eloquent y la clase abstracta sin uso son deudas que deberán resolverse. Si Phase 03 se implementa correctamente, A03 debería empezar a demostrar por qué el Strategy Pattern vale la pena.
