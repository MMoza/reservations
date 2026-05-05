# 🏗 Arquitectura Comparada – Sistema de Reservas

Este proyecto es un laboratorio práctico para comparar distintas decisiones arquitectónicas aplicadas al mismo dominio funcional.

El objetivo no es demostrar que una arquitectura es mejor que otra, sino analizar cómo distintas aproximaciones responden ante el aumento progresivo de complejidad en el dominio.

---

# 🎯 Objetivos del Proyecto

Este experimento busca evaluar cómo diferentes estilos arquitectónicos afectan:

- 📦 Complejidad estructural
- 🧠 Complejidad cognitiva
- 🧪 Testeabilidad
- 🔗 Nivel de acoplamiento
- 📈 Escalabilidad del diseño
- 🔄 Capacidad de evolución
- 🛠 Coste de cambio ante nuevas reglas

El dominio funcional se mantiene constante.

**Lo único que cambia es la arquitectura.**

---

# 🧩 Dominio Común

Sistema de reservas multi-producto con:

- Productos reservables por fechas
- Extras opcionales (por noche o fijos)
- Cálculo de precio total
- Reglas de negocio progresivamente más complejas

## 🔌 Endpoints Base

Cada arquitectura expone exactamente los mismos endpoints, variando únicamente el prefijo y la versión de complejidad:

### Crear una reserva

```http
POST /api/{arquitectura}/v{version}/reservation
Content-Type: application/json
```
### Obtener una reserva

```http
GET /api/{arquitectura}/v{version}/reservation/{id}
Accept: application/json
```

Cada arquitectura implementa exactamente la misma funcionalidad externa.

---

# 🏗 Arquitecturas Implementadas

## A01 – Monolithic Eloquent

**Active Record puro.**

- Lógica directamente en modelos y/o controlador
- Uso directo de Eloquent
- Sin separación entre dominio e infraestructura

Representa el enfoque más común en aplicaciones CRUD.

### Características

- Rápido de desarrollar
- Baja abstracción
- Alto acoplamiento al framework
- Escala mal cuando crecen reglas complejas

---

## A02 – Repository Pattern

Separación entre capas:

- Controller
- Service (si aplica)
- Repository Interface
- Implementación Eloquent

Introduce inversión de dependencias y desacoplamiento de infraestructura.

### Características

- Mayor organización
- Mejor testabilidad
- Más código y abstracción
- Dominio aún potencialmente anémico

---

## A03 – Strategy + Polymorphism

El comportamiento depende del tipo de reserva.

- Reservation abstracta
- Implementaciones concretas por tipo
- Estrategias de cálculo de precio

Permite encapsular reglas específicas por contexto.

### Características

- Dominio más expresivo
- Mejor extensibilidad por tipo
- Mayor complejidad estructural
- Más clases y jerarquías

---

## A04 – Decorator Domain

Composición dinámica del comportamiento.

- BaseReservation
- Decorators para extras y reglas
- Aplicación real del principio Open/Closed

Permite combinar reglas sin modificar código existente.

### Características

- Máxima flexibilidad
- Extensión por composición
- Bajo acoplamiento
- Mayor complejidad conceptual

---

# 📈 Evolución por Fases

Cada arquitectura evoluciona a través de 4 fases de complejidad creciente.

Cada fase tiene su propio documento de conclusiones comparativas:

- [Phase 01 – Cálculo Base](docs/phase-01-conclusiones.md)
- [Phase 02 – Reglas Condicionales](docs/phase-02-conclusiones.md)
- [Phase 03 – Comportamiento Polimórfico](docs/phase-03-conclusiones.md)
- [Phase 04 – Reglas Combinables y Dinámicas](docs/phase-04-conclusiones.md)

---

## 🧩 Phase 01 – Cálculo Base

- Base price
- Extras fijos o por noche
- Total acumulado

Objetivo: establecer baseline funcional.

---

## 🧩 Phase 02 – Reglas Condicionales

Se introducen reglas transversales:

- Descuentos por volumen
- Promociones combinadas
- Precio mínimo garantizado
- Validaciones dependientes

Aquí empieza a tensionarse el diseño monolítico.

---

## 🧩 Phase 03 – Comportamiento Polimórfico

- Tipos de reserva distintos (ej: hotel, evento)
- Fórmulas diferentes por tipo
- Impuestos o comisiones específicas
- Restricciones según tipo

Aquí se evalúa la capacidad de encapsular comportamiento.

---

## 🧩 Phase 04 – Reglas Combinables y Dinámicas

- Extras que modifican porcentaje total
- Reglas que afectan a otras reglas
- Dependencias entre promociones
- Modificadores encadenables

Se pone a prueba la extensibilidad real del diseño.

---

# 🧪 Criterios de Evaluación

Durante el experimento se analizarán:

- Número de clases
- Líneas de código
- Complejidad estructural
- Facilidad para añadir nuevas reglas
- Nivel de acoplamiento
- Dificultad de testing
- Claridad del modelo de dominio
- Impacto del crecimiento del dominio en cada arquitectura

---

# 📊 Estado del Proyecto

| Arquitectura              | Phase 01 | Phase 02 | Phase 03 | Phase 04 |
|---------------------------|----------|----------|----------|----------|
| A01 Monolithic            | ✅       | ✅       | ✅       | ✅       |
| A02 Repository            | ✅       | ✅       | ✅       | ✅       |
| A03 Strategy              | ✅       | ✅       | ✅       | ✅       |
| A04 Decorator             | ✅       | ✅       | ✅       | ✅       |

Todas las fases implementadas. 77 tests, 376 assertions.

---

# 📚 Documentación

### Documentos por fase (una por arquitectura)

| | A01 Monolithic | A02 Repository | A03 Strategy | A04 Decorator |
|---|---|---|---|---|
| Phase 01 | [doc](docs/phase-01/a01-monolithic.md) | [doc](docs/phase-01/a02-repository.md) | [doc](docs/phase-01/a03-strategy.md) | [doc](docs/phase-01/a04-decorator.md) |
| Phase 02 | [doc](docs/phase-02/a01-monolithic.md) | [doc](docs/phase-02/a02-repository.md) | [doc](docs/phase-02/a03-strategy.md) | [doc](docs/phase-02/a04-decorator.md) |
| Phase 03 | [doc](docs/phase-03/a01-monolithic.md) | [doc](docs/phase-03/a02-repository.md) | [doc](docs/phase-03/a03-strategy.md) | [doc](docs/phase-03/a04-decorator.md) |
| Phase 04 | [doc](docs/phase-04/a01-monolithic.md) | [doc](docs/phase-04/a02-repository.md) | [doc](docs/phase-04/a03-strategy.md) | [doc](docs/phase-04/a04-decorator.md) |

### Documentos de conclusiones por fase (comparativa)

- [Phase 01](docs/phase-01-conclusiones.md)
- [Phase 02](docs/phase-02-conclusiones.md)
- [Phase 03](docs/phase-03-conclusiones.md)
- [Phase 04](docs/phase-04-conclusiones.md)

---

# 📝 Conclusiones

_(Se completará tras implementar todas las fases.)_

La arquitectura no es buena ni mala por sí misma. Es adecuada o inadecuada según la complejidad del dominio, la previsión de crecimiento, el coste de mantenimiento esperado y la necesidad de extensibilidad. Este proyecto busca evidenciar cómo el diseño debe justificarse por el problema, no por preferencia técnica.

Se analizará:

- Qué arquitectura ofrece mejor equilibrio simplicidad / escalabilidad
- Cuándo merece la pena sobrearquitecturar
- Cuándo mantener simplicidad pragmática
- Qué enfoque es más sostenible a largo plazo

---

# 📌 Nota

Este proyecto tiene fines educativos y de análisis arquitectónico.

No pretende ser una implementación productiva lista para entornos reales, sino un entorno controlado para experimentar con diseño de software y evolución del dominio.