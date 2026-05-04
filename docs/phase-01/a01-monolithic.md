# Phase 01 – A01 Monolithic Eloquent

## Resumen

Active Record puro. Toda la lógica de negocio vive directamente en el controlador, usando los modelos Eloquent tanto como entidades de persistencia como objetos de cálculo. Sin capas intermedias.

---

## Estructura

```
Phase_01/
├── Catalog/
│   └── ProductCatalog.php          (50 líneas) – Catálogo estático en memoria
├── Models/
│   ├── Reservation.php             (25 líneas) – Modelo + cálculo del total
│   └── Extra.php                   (19 líneas) – Modelo simple
├── Requests/
│   └── StoreReservationRequest.php (42 líneas) – Validación del request
└── Controllers/
    └── ReservationController.php   (83 líneas) – TODA la lógica de negocio
```

**Total: 5 archivos, 218 líneas**

---

## Puntos fuertes

- **Velocidad de desarrollo:** Se puede implementar todo en una sola sesión. Sin interfaces, sin contratos, sin inyección de dependencias.
- **Bajo coste cognitivo:** Un solo archivo para entender (el controlador). No hay que saltar entre capas.
- **Mínimo boilerplate:** No hay clases vacías, interfaces sin implementación, ni decoradores innecesarios.
- **Resultado correcto:** El test de equivalencia confirma que produce el mismo output que las otras 3 arquitecturas.

---

## Puntos débiles

- **Controlador sobrecargado:** 83 líneas mezclan cálculo de precio, creación de extras, validación de catálogo y persistencia en un solo método.
- **Imposible mockear:** No hay interfaces ni abstracciones. Para testear el cálculo de precio necesitas la base de datos real.
- **Sin encapsulamiento de dominio:** La fórmula `base_price + extras.sum('price')` vive en el modelo como accessor, pero toda la lógica de cálculo está en el controlador, no en un objeto de dominio.
- **Sin inversión de dependencias:** El controlador depende directamente de Eloquent y del catálogo estático. Cambiar la fuente de datos implica modificar el controlador.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Implementación inicial | **Muy bajo** – 5 archivos, todo directo |
| Lógica de cálculo | **Trivial** – inline en el controlador |
| Testeabilidad | **Alto** – sin abstracciones, todo acoplado a Eloquent |
| Añadir nueva regla | **Alto** – hay que modificar el controlador directamente |

La parte más cara no es la implementación inicial, sino el **coste futuro de cada cambio**: cada nueva regla de negocio se añade al mismo método `store()`, que ya tiene 83 líneas y cero separación de responsabilidades.

---

## Lecciones para Phase 02

Phase 02 introduce 4 reglas condicionales nuevas (descuentos por volumen, promociones combinadas, precio mínimo, validaciones dependientes). En esta arquitectura, todas irán al controlador. El método `store()` pasará de ~83 a probablemente ~150+ líneas. Este es exactamente el punto de quiebre que se quiere demostrar: el monolito funciona hasta que no funciona.
