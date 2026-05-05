# Phase 03 – Comportamiento Polimórfico

## Objetivo

Introducir tipos de reserva distintos (hotel, evento) con fórmulas de precio diferentes: impuestos por tipo y comisiones por tipo. Aquí se evalúa la capacidad de cada arquitectura para encapsular comportamiento específico por tipo.

---

## Reglas de negocio

| Regla | Hotel | Evento |
|-------|-------|--------|
| Impuesto | 10% sobre base_price | 5% sobre base_price |
| Comisión | 0% | 3% sobre base_price |
| Restricción mínima | Sin restricción | Mínimo 3 noches |
| Formato tax_rate | `Tax 10%` | `Tax 5%` |

Cuando hay múltiples productos de distinto tipo, se usa el **primer producto** como tipo primario.

---

## Cálculo de referencia

### Payload: Evento, 5 noches

```json
{
  "products": [
    {
      "product_id": 3,
      "dates": ["2026-03-01", "2026-03-02", "2026-03-03", "2026-03-04", "2026-03-05"]
    }
  ]
}
```

### Desglose

| Concepto | Cálculo | Resultado |
|----------|---------|-----------|
| Producto 3 (Sala conferencias) | $300 × 5 noches | $1,500 |
| **base_price** | | **$1,500** |
| Impuesto evento (5%) | $1,500 × 0.05 | $75 |
| Comisión evento (3%) | $1,500 × 0.03 | $45 |
| **tax_amount** | | **$75** |
| **tax_rate** | | `Tax 5%` |
| **commission_amount** | | **$45** |

### Response esperado

```json
{
  "id": 1,
  "type": "multi-product",
  "base_price": 1500,
  "tax_amount": 75,
  "tax_rate": "Tax 5%",
  "commission_amount": 45,
  "extras": []
}
```

---

## Métricas comparativas

| Métrica | A01 Monolithic | A02 Repository | A03 Strategy | A04 Decorator |
|---------|----------------|----------------|--------------|---------------|
| Archivos | 6 | 10 | 15 | 19 |
| Líneas de código | 398 | 503 | 727 | 878 |
| Controlador | 141 líneas | 30 líneas | 32 líneas | 32 líneas |
| Servicio/Lógica | 141 líneas (controller) | 126 líneas (service) | 103 líneas (service) | 174 líneas (service) |
| Impuestos por tipo | Lookup inline en controller | Lookup inline en service | Delegado en PricingStrategy | TaxDecorator (objeto) |
| Comisión por tipo | Lookup inline en controller | Lookup inline en service | Delegado en PricingStrategy | CommissionDecorator (objeto) |
| Test de equivalencia | ✅ | ✅ | ✅ | ✅ |

### Crecimiento respecto a Phase 02

| Métrica | A01 | A02 | A03 | A04 |
|---------|-----|-----|-----|-----|
| +Archivos | 0 | 0 | 0 | +2 |
| +Líneas | +72 (+22%) | +96 (+24%) | +204 (+39%) | +207 (+31%) |
| +Controlador | +18 líneas | 0 líneas | 0 líneas | 0 líneas |
| +Servicio | +18 líneas (controller) | +20 líneas | +15 líneas | +12 líneas |

---

## Conclusiones

### A01 – Monolithic Eloquent
- **El punto de quiebre confirmado:** 141 líneas en el controlador, 7 responsabilidades. Añadir comportamiento por tipo implica más `if/else` inline.
- **Lookup directo:** `PricingRules::taxRates()[$primaryType]` funciona pero no escala: si un tipo necesita lógica compleja (impuestos compuestos, comisiones escalonadas), toda esa lógica va al controlador.
- **Observación:** Phase 03 es donde el monolito deja de ser viable para mantenimiento a largo plazo.

### A02 – Repository Pattern
- **Servicio de 126 líneas:** Mismo problema que A01 pero desplazado al servicio. La separación controller/service no resuelve la complejidad algorítmica.
- **Lookup directo, mismo que A01:** `PricingRules::taxRates()[$primaryType]` en el servicio. La única diferencia es el archivo donde vive.
- **Observación:** A02 confirma que Repository Pattern no es suficiente para comportamiento polimórfico.

### A03 – Strategy + Polymorphism
- **Strategy Pattern justificado:** Phase 03 es donde A03 brilla. `PricingStrategy::calculateTax()` y `calculateCommission()` encapsulan las fórmulas. Un nuevo tipo requiere una nueva estrategia, no modificar código existente.
- **Inconsistencias persistentes:** `Reservations` abstracta sin uso, `MultiProductReservation` heredando de Eloquent. El Strategy Pattern se ve comprometido por estas deudas.
- **Más líneas que A02:** 727 vs 503 líneas. La ventaja no es en cantidad de código sino en organización y extensibilidad.
- **Observación:** A03 demuestra que el Strategy Pattern vale la pena cuando hay comportamiento por tipo, pero la implementación tiene deudas que limitan su pureza.

### A04 – Decorator Domain
- **Decorators por regla:** `TaxDecorator` y `CommissionDecorator` son objetos independientes. Cada uno encapsula su lógica. Si un tipo necesita una fórmula compleja, solo se modifica ese decorador.
- **Servicio de 174 líneas:** El orquestador crece porque debe construir la cadena de decoradores. Cada nuevo tipo de decorador añade 2-3 líneas al servicio.
- **Mayor inversión en abstracción:** 19 archivos, 878 líneas. El coste es alto pero cada regla está aislada y testeable en solitario.
- **Observación:** A04 ofrece la mejor extensibilidad pero con el mayor coste en complejidad estructural.

---

## Verificación

Test de equivalencia: `Phase03EquivalenceTest` – 2 tests, 34 assertions.
Todas las arquitecturas producen resultados idénticos con el mismo input.

---

## Tensión arquitectónica

Phase 03 es el momento donde A03 y A04 empiezan a demostrar su valor:

- **A01 y A02** siguen el mismo patrón: lookup en PricingRules + lógica inline. La diferencia es dónde vive (controller vs service).
- **A03** encapsula las fórmulas en la estrategia, permitiendo nuevas estrategias sin tocar el servicio.
- **A04** encapsula cada regla en un decorador, permitiendo nuevas reglas sin tocar código existente.

La pregunta es: ¿merece la pena el coste extra de A03 (727 líneas) y A04 (878 líneas) frente a la simplicidad de A02 (503 líneas)? La respuesta depende de la previsión de nuevos tipos de producto y reglas de pricing.
