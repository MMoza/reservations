# Phase 02 – A01 Monolithic Eloquent

## Resumen

Todas las reglas condicionales se añaden directamente al controlador, donde ya vivía la lógica de Phase 01. Se introduce un nuevo catálogo `PricingRules` para centralizar la configuración de las reglas, pero la lógica de aplicación sigue inline en el método `store()`.

---

## Estructura

```
Phase_02/
├── Catalog/
│   ├── ProductCatalog.php              (50 líneas) – Catálogo estático
│   └── PricingRules.php                (38 líneas) – NUEVO: configuración de reglas
├── Models/
│   ├── Reservation.php                 (28 líneas) – Agregados discount_amount y discount_reason
│   └── Extra.php                       (20 líneas) – Sin cambios
├── Requests/
│   └── StoreReservationRequest.php     (67 líneas) – Agregado withValidator para validaciones dependientes
└── Controllers/
    └── ReservationController.php       (123 líneas) – TODA la lógica condicional
```

**Total: 6 archivos, 326 líneas** (vs 5 archivos, 218 líneas en Phase 01)

**Crecimiento: +1 archivo, +108 líneas (+50%)**

---

## Nuevas reglas implementadas

| Regla | Implementación |
|---|---|
| Descuento por volumen | `foreach` sobre `PricingRules::volumeDiscounts()` buscando el primer threshold alcanzado |
| Promoción combinada | `array_intersect` entre productos reservados y productos requeridos por la promo |
| Precio mínimo garantizado | Comparación simple, retorna 422 si no se cumple |
| Validaciones dependientes | `withValidator()` en el FormRequest, valida noches mínimas por extra |

---

## Puntos fuertes

- **Todo en un lugar:** No hay que buscar en múltiples archivos para entender cómo se calcula el precio con descuentos. Todo está en `store()`.
- **Configuración centralizada:** `PricingRules` separa los valores de las reglas de su aplicación. Cambiar un porcentaje no requiere tocar el controlador.
- **Rápido de implementar:** Las 4 reglas se añadieron en una sola sesión sin crear nuevas capas ni patrones.
- **Migración sencilla:** Solo se añadieron 2 columnas a la tabla `reservations`. Sin cambios en la estructura de las tablas existentes.

---

## Puntos débiles

- **Controlador de 123 líneas:** El método `store()` pasó de 83 a 123 líneas. Mezcla cálculo de precio base, cálculo de descuentos, validación de precio mínimo, y persistencia.
- **Acumulación de responsabilidades:** El controlador ahora hace:
  1. Validar el request
  2. Calcular precio base
  3. Calcular descuentos por volumen
  4. Calcular descuentos por promoción combinada
  5. Verificar precio mínimo
  6. Persistir la reserva y los extras
  7. Retornar la respuesta

- **Lógica condicional inline:** Los `foreach` y `if` de descuentos están directamente en el controlador. No hay un objeto que represente un "descuento" ni una "promoción".
- **Formato de reason como string:** `discount_reason` es un string concatenado (`'volume-10% + combined-promo-5%'`). No hay estructura ni validación de este formato.
- **Código duplicado en el cálculo de descuentos:** Ambos tipos de descuento siguen el mismo patrón (`$discount = $base * $percentage; $amount += $discount;`) pero no se ha extraído a un método reutilizable.

---

## Coste de desarrollo

| Aspecto | Coste |
|---|---|
| Nueva regla (PricingRules) | **Bajo** – un archivo con métodos estáticos |
| Lógica de descuentos | **Medio** – inline en el controlador, fácil de escribir, difícil de mantener |
| Validaciones dependientes | **Bajo** – `withValidator` es un hook natural de Laravel |
| Migración de base de datos | **Bajo** – 2 columnas nuevas |
| Testeabilidad | **Alto** – sin abstracciones, se necesitan tests de integración con BD |
| Añadir Phase 03 | **Alto** – comportamiento polimórfico requerirá reescribir gran parte del controlador |

El coste más alto fue **gestionar la interacción entre reglas**: los descuentos son acumulables, el precio mínimo se aplica después de todos los descuentos, y las validaciones deben ejecutarse antes del cálculo. Todo esto se maneja con orden de ejecución implícito en el código.

---

## Lecciones para Phase 03

Phase 03 introducirá comportamiento polimórfico (tipos de reserva distintos con fórmulas diferentes). En esta arquitectura, eso significará más `if/else` y `switch` en el controlador para determinar el tipo de reserva y aplicar la fórmula correspondiente. El método `store()` superará fácilmente las 200 líneas. Este es exactamente el punto de quiebre que se quiere demostrar: el monolito aguanta Phase 01, sufre en Phase 02, y colapsa en Phase 03.
