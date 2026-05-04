# Phase 01 – A02 Repository Pattern

## Resumen

Separa persistencia de lógica de negocio mediante un repositorio abstracto. El controlador delega a un servicio, y el servicio usa una interfaz de repositorio en lugar de Eloquent directamente. Los modelos siguen siendo anémicos.

---

## Estructura

```
Phase_01/
├── Catalog/
│   └── ProductCatalog.php                (50 líneas) – Catálogo estático
├── Repositories/
│   ├── Contracts/
│   │   └── ReservationRepositoryInterface.php (13 líneas) – Contrato
│   └── Eloquent/
│       └── EloquentReservationRepository.php  (30 líneas) – Implementación
├── Services/
│   └── ReservationService.php            (61 líneas) – Lógica de negocio
├── Models/
│   ├── Reservation.php                   (25 líneas) – Modelo anémico
│   └── Extra.php                         (19 líneas) – Modelo anémico
├── Requests/
│   └── StoreReservationRequest.php       (42 líneas) – Validación
└── Controllers/
    └── ReservationController.php         (29 líneas) – Delega al servicio
```

**Total: 8 archivos, 269 líneas**

---

## Puntos fuertes

- **Controlador delgado:** 29 líneas frente a 83 de A01. El controlador solo valida y delega.
- **Inversión de dependencias:** El servicio depende de `ReservationRepositoryInterface`, no de Eloquent directamente. Esto permite mockear el repositorio en tests unitarios.
- **Capa de servicio clara:** Toda la lógica de cálculo vive en `ReservationService`. Es el único lugar donde hay que buscar para entender cómo se calcula el precio.
- **Fácil swap de infraestructura:** Cambiar de Eloquent a otra implementación solo requiere crear otra clase que implemente la interfaz del repositorio.

---

## Puntos débiles

- **Lógica aún inline:** El servicio calcula precios directamente en un bucle `foreach`. No hay un objeto de dominio que encapsule el cálculo.
- **Modelos anémicos:** `Reservation` y `Extra` son simples contenedores de datos con getters/setters. No tienen comportamiento.
- **Boilerplate intermedio:** +3 archivos y +51 líneas respecto a A01, pero el dominio sigue siendo procedural. La capa de repositorio es esencialmente un wrapper alrededor de Eloquent (`Reservation::create()`, `$reservation->extras()->create()`).
- **Servicio conoce el catálogo:** `ReservationService` depende directamente de `ProductCatalog`. No hay separación entre el dominio de reservas y el dominio de catálogo.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Implementación inicial | **Bajo** – la estructura es clara y predecible |
| Interfaz + implementación repositorio | **Bajo** – CRUD directo, poca complejidad |
| Lógica de cálculo | **Bajo** – inline en el servicio, sin patrones adicionales |
| Testeabilidad | **Medio** – se puede mockear el repositorio, pero el cálculo sigue acoplado al servicio |
| Añadir nueva regla | **Medio** – se modifica el servicio, que sigue siendo un solo archivo grande |

El coste adicional respecto a A01 (+51 líneas, +3 archivos) se justifica por la capacidad de mockear el repositorio. Pero el dominio sigue siendo procedural, lo que significa que Phase 02 seguirá sufriendo igual que A01, solo que en el servicio en lugar del controlador.

---

## Lecciones para Phase 02

Phase 02 añadirá reglas condicionales al servicio. La ventaja respecto a A01 es que el servicio es testeable de forma aislada (con un repositorio mock). La desventaja es que la lógica de cálculo sigue siendo procedural: más `if/else`, más bucles, más complejidad ciclomática en un solo archivo.
