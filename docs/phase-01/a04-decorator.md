# Phase 01 – A04 Decorator Domain

## Resumen

Usa el patrón Decorator para componer el precio de la reserva de forma dinámica. Los objetos de pricing son puros (no extienden de Eloquent). La separación entre dominio y persistencia es total. Es la arquitectura con mayor inversión en abstracción.

---

## Estructura

```
Phase_01/
├── Domain/
│   ├── Catalog/
│   │   └── ProductCatalog.php            (50 líneas) – Catálogo estático
│   └── Pricing/
│       ├── ReservationComponent.php      (12 líneas) – Interfaz
│       ├── BaseReservation.php           (21 líneas) – Componente base (precio 0)
│       ├── ReservationDecorator.php      (25 líneas) – Base para decoradores
│       ├── Decorators/
│       │   ├── ProductBasePriceDecorator.php (19 líneas)
│       │   └── ExtraChargeDecorator.php  (25 líneas)
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (12 líneas)
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (26 líneas)
├── Services/
│   └── CreateReservationService.php      (118 líneas) – Orquesta decoradores
├── Models/
│   ├── Reservation.php                   (26 líneas)
│   └── Extra.php                         (20 líneas)
├── Requests/
│   └── StoreReservationRequest.php       (37 líneas)
└── Controllers/
    └── ReservationController.php         (32 líneas)
```

**Total: 13 archivos, 423 líneas**

---

## Puntos fuertes

- **Dominio puro:** Los objetos de pricing (`ReservationComponent`, `BaseReservation`, decoradores) no dependen de Eloquent ni de ningún framework. Son clases PHP puras.
- **Separación total dominio/infraestructura:** El dominio calcula precios, el repositorio persiste. No hay mezcla. Cada capa tiene una responsabilidad clara.
- **Extensibilidad por composición:** Añadir un nuevo tipo de cargo (ej: impuesto, comisión) solo requiere un nuevo decorador. No se modifica código existente (Open/Closed Principle).
- **Inmutabilidad implícita:** Cada decorador envuelve al anterior sin modificarlo. El estado no se muta, se construye una nueva cadena.
- **Resultado correcto:** El test de equivalencia confirma que produce el mismo output que las otras 3 arquitecturas.

---

## Puntos débiles

- **Complejidad desproporcionada para Phase 01:** 13 archivos y 423 líneas para un cálculo que en A01 ocupa 83 líneas. La inversión en abstracción no se justifica con las reglas actuales.
- **Servicio orquestador grande:** `CreateReservationService` tiene 118 líneas porque debe construir manualmente la cadena de decoradores con bucles y condicionales. Esta lógica de composición es difícil de seguir.
- **Decoradores con identidad limitada:** `ExtraChargeDecorator` solo tiene un nombre y un precio. No hay forma de distinguir entre tipos de extras más allá del nombre string.
- **Difícil de depurar:** Si un precio es incorrecto, hay que desenrollar mentalmente una cadena de N decoradores para encontrar dónde está el error.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Implementación inicial | **Alto** – 13 archivos, patrón Decorator completo |
| Objetos de dominio | **Medio** – interfaces y clases base, pero luego cada decorador es simple |
| Servicio orquestador | **Alto** – 118 líneas construyendo decoradores manualmente |
| Testeabilidad | **Alto** – dominio puro es fácilmente testeable sin base de datos |
| Añadir nueva regla de precio | **Bajo** – nuevo decorador, sin tocar código existente |
| Añadir nueva regla de flujo | **Alto** – el servicio orquestador sigue creciendo |

La parte más cara fue **construir la infraestructura del patrón Decorator** (interfaz, base, abstracta, 2 decoradores concretos). Pero una vez construida, añadir un nuevo tipo de cargo es barato.

---

## Lecciones para Phase 02

Phase 02 introduce reglas que no son cargos directos sino **modificadores del total** (descuentos porcentuales, precio mínimo). El patrón Decorator puede modelar descuentos como decoradores, pero la lógica condicional de cuándo aplicarlos (≥7 noches, productos combinados) seguirá viviendo en el servicio orquestador. Aquí se verá si la inversión en dominio puro vale la pena cuando las reglas se vuelven más complejas.
