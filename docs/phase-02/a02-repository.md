# Phase 02 – A02 Repository Pattern

## Resumen

Las reglas condicionales se añaden al servicio, no al controlador. El controlador sigue siendo delgado (~30 líneas) porque delega toda la lógica al `ReservationService`. Se introduce `MinimumPriceException` como excepción de dominio que el controlador captura y convierte en respuesta HTTP 422.

---

## Estructura

```
Phase_02/
├── Catalog/
│   ├── ProductCatalog.php              (50 líneas) – Catálogo estático
│   └── PricingRules.php                (38 líneas) – Configuración de reglas
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (18 líneas) – +1 método
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (42 líneas) – +1 método
├── Services/
│   └── ReservationService.php          (106 líneas) – Lógica condicional
├── Exceptions/
│   └── MinimumPriceException.php       (12 líneas) – NUEVO
├── Models/
│   ├── Reservation.php                 (28 líneas) – Agregados discount_amount/reason
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (67 líneas) – Agregado withValidator
└── Controllers/
    └── ReservationController.php       (30 líneas) – try/catch simple
```

**Total: 10 archivos, 411 líneas** (vs Phase 01: 8 archivos, 269 líneas)

**Crecimiento: +2 archivos, +142 líneas (+53%)**

---

## Puntos fuertes

- **Controlador sigue delgado:** 30 líneas con un try/catch que convierte la excepción en respuesta 422. La separación controlador/servicio se mantiene intacta.
- **Excepción como mecanismo de error:** `MinimumPriceException` expresa claramente la intención del fallo. El servicio no retorna arrays con errores ni nulls ambiguos; lanza una excepción con un mensaje formateado.
- **Servicio testable:** Toda la lógica de reglas condicionales vive en el servicio, que se puede testear con un repositorio mock. Los 8 tests funcionales confirman el comportamiento correcto.
- **Repositorio extendido limpiamente:** `updateDiscount` se añade a la interfaz y a la implementación Eloquent sin afectar otros métodos.
- **Misma lógica, mejor ubicación:** El cálculo de descuentos es idéntico al de A01, pero aquí vive en el servicio donde tiene sentido, no en el controlador.

---

## Puntos débiles

- **Lógica aún procedural en el servicio:** El `ReservationService` tiene 106 líneas con bucles, `if/else`, y `foreach` para descuentos. No hay objetos que representen "descuento", "promoción" o "regla".
- **Servicio conoce todo:** Depende de `ProductCatalog`, `PricingRules`, `ReservationRepositoryInterface`, y `MinimumPriceException`. Demasiadas responsabilidades en una sola clase.
- **Boilerplate creciente:** +2 archivos y +142 líneas respecto a Phase 01, pero el dominio sigue siendo procedural. La capa de repositorio sigue siendo un wrapper de Eloquent.
- **Excepción sin handler global:** Si `MinimumPriceException` se lanzara desde otro lugar no protegido por try/catch, se convertiría en un error 500. Idealmente habría un exception handler global.
- **Servicio de 106 líneas:** Aunque mejor que el controlador de 123 líneas de A01, sigue siendo un método `createReservation` largo que hace demasiadas cosas.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Excepción personalizada | **Bajo** – 12 líneas, clase trivial |
| Actualizar interfaz repositorio | **Bajo** – 1 método nuevo |
| Implementación Eloquent | **Bajo** – CRUD directo |
| Lógica de descuentos en servicio | **Medio** – inline, pero al menos en la capa correcta |
| Validaciones dependientes | **Bajo** – `withValidator` igual que A01 |
| Testeabilidad | **Medio** – el servicio es mockeable pero la lógica sigue procedural |
| Añadir Phase 03 | **Alto** – comportamiento polimórfico requerirá reescribir el servicio |

El coste más alto fue **gestionar la interacción entre reglas en el servicio**: calcular descuentos acumulables, verificar precio mínimo, y actualizar la reserva en una secuencia correcta. La ventaja respecto a A01 es que esta lógica está aislada en el servicio y no contaminado el controlador.

---

## Lecciones para Phase 03

Phase 03 introducirá comportamiento polimórfico (tipos de reserva distintos con fórmulas diferentes). En esta arquitectura, el servicio tendrá que determinar el tipo de reserva y aplicar la fórmula correspondiente mediante `switch` o condicionales. La ventaja es que la lógica polimórfica se añadirá al servicio sin tocar el controlador. Pero el servicio seguirá siendo una clase "Dios" que orquesta todo. Este es exactamente el punto donde A03 y A04 deberían empezar a brillar.
