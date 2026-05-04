# Phase 01 – A03 Strategy + Polymorphism

## Resumen

Introduce un patrón Strategy para encapsular el cálculo de precios. El dominio tiene una clase `MultiProductReservation` que calcula el total delegando en un `PricingStrategy`. Sin embargo, `MultiProductReservation` extiende del Eloquent Model, lo que mezcla dominio con persistencia.

---

## Estructura

```
Phase_01/
├── Domain/
│   ├── Catalog/
│   │   ├── ProductCatalog.php            (51 líneas) – Catálogo con batch lookup
│   │   └── ProductCollection.php         (18 líneas) – Wrapper inmutable del catálogo
│   ├── Pricing/
│   │   ├── PricingStrategy.php           (9 líneas) – Interfaz
│   │   └── BasicPricingStrategy.php      (23 líneas) – Implementación
│   └── Reservations/
│       ├── Reservations.php              (19 líneas) – Abstracta (NO USADA)
│       └── MultiProductReservation.php   (52 líneas) – Calcula precio
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (11 líneas)
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (24 líneas)
├── Services/
│   └── CreateReservationService.php      (47 líneas) – Orquestación
├── Models/
│   ├── Reservation.php                   (25 líneas) – Modelo Eloquent
│   └── Extra.php                         (19 líneas) – Modelo Eloquent
├── Requests/
│   └── StoreReservationRequest.php       (42 líneas) – Validación
└── Controllers/
    └── ReservationController.php         (31 líneas)
```

**Total: 13 archivos, 371 líneas**

---

## Puntos fuertes

- **Cálculo encapsulado:** `PricingStrategy` separa la fórmula de precio del flujo de creación. Añadir una nueva fórmula de precio solo requiere una nueva clase que implemente la interfaz.
- **Batch lookup:** `ProductCatalog` usa `findProductsByIds` y `findExtrasByIds` para hacer una sola búsqueda en lugar de N queries individuales. Diseño más eficiente.
- **ProductCollection:** Abstrae el catálogo como un objeto de dominio inmutable, no como un llamado estático directo.
- **Servicio delgado:** 47 líneas porque delega el cálculo a `MultiProductReservation` y la persistencia al repositorio.

---

## Puntos débiles

- **`MultiProductReservation` extiende de Eloquent:** Esto mezcla persistencia con lógica de dominio. Un objeto de dominio no debería saber de bases de datos. Esto contradice el propósito del patrón Strategy.
- **Clase `Reservations` no usada:** La clase abstracta `Reservations` existe pero `MultiProductReservation` no la extiende. Es código muerto que añade confusión.
- **Estrategia instanciada directamente:** `BasicPricingStrategy` se crea con `new` en el servicio, no se inyecta. Esto anula parcialmente el beneficio del patrón Strategy.
- **Complejidad sin beneficio completo:** 13 archivos y 371 líneas (71% más que A02), pero el dominio no es puro porque hereda de Eloquent.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Implementación inicial | **Medio-Alto** – 13 archivos, jerarquías incompletas |
| Strategy Pattern | **Bajo** – una interfaz y una implementación simples |
| Limpieza arquitectónica | **Alto** – la mezcla Eloquent/dominio requiere refactorización |
| Testeabilidad | **Medio** – el dominio depende de Eloquent a través de herencia |
| Añadir nueva regla | **Medio** – depende de si la regla es de precio (Strategy) o de flujo (Service) |

El mayor coste fue **diseñar la estructura sin completarla**: la clase abstracta sin uso y la herencia de Eloquent son decisiones inconsistentes que deberán resolverse en fases posteriores.

---

## Lecciones para Phase 02

Phase 02 introduce reglas condicionales que no son de precio (descuentos, promociones, validaciones). El patrón Strategy solo cubre el cálculo de precio base. Para las reglas condicionales, A03 tendrá la misma tensión que A02: añadir `if/else` en el servicio o crear nuevas abstracciones. La inconsistencia arquitectónica (dominio que hereda de Eloquent) se notará aún más.
