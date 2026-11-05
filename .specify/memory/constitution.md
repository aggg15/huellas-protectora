# Constitution de Huellas

Reglas comunes para desarrollar el sistema de gestión de adopciones de la protectora Huellas.
Corresponde al paso 2.1 de la guía del proyecto SDD, parte I.

**Estado:** propuesta inicial preparada para revisión conjunta de los dos integrantes.
La redacción de este documento no acredita una ratificación del equipo ni la ejecución de
`/speckit.constitution`.

## Principios fundamentales

### I. Fidelidad a la narrativa y alcance explícito

La descripción SI-30 define las necesidades funcionales; la guía específica y el enunciado general
definen el proceso y los entregables. Toda funcionalidad debe justificarse mediante una necesidad de
esas fuentes y vincularse a una de las ocho historias HU-01 a HU-08.

Se deben conservar las reglas expresas de cuarentena, disponibilidad pública, solicitudes, reservas,
adopciones, acogidas, turnos y apadrinamientos. Las ambigüedades se registrarán y resolverán mediante
`clarify`, distinguiendo el texto del enunciado de las decisiones justificadas del equipo. La
Constitution no resuelve por sí sola las preguntas pendientes de las historias.

Quedan fuera la contabilidad general y las campañas de recogida de pienso. No se incorporarán
integraciones, módulos o automatismos ajenos a las necesidades descritas sin justificar su encaje
en el alcance. El contexto de esta iteración se limita exclusivamente a la Práctica Grupal; no se usan
las entregas de otros proyectos como fuente operativa o funcional.

### II. Especificación previa, criterios verificables y trazabilidad

Cada historia debe disponer de una especificación antes de implementar su funcionalidad. La spec
debe identificar rol, necesidad, beneficio, reglas de negocio, criterios de aceptación, casos límite,
dependencias y exclusiones. Debe centrarse en el qué y el porqué; el cómo técnico corresponde al plan.

Los criterios se redactarán con resultados observables, preferentemente en formato
«Dado… Cuando… Entonces…». Se conservarán los identificadores HU-01 a HU-08 y CA-XX-YY; los nuevos
criterios recibirán identificadores que no se confundan con los existentes.

Debe poder recorrerse la relación historia y criterio → decisión del plan → tarea → implementación
y comprobación. No se eliminará un requisito válido para ocultar una omisión del plan o un fallo del
código. Una tarea marcada como terminada no sustituye la evidencia de aceptación.

### III. Una aplicación y una base técnica compartidas

Las ocho historias forman parte de una misma aplicación. El equipo debe acordar un plan técnico y un
modelo de datos comunes antes de desarrollar las historias de forma separada. La pila de referencia
es la propuesta en SI-30: Symfony, Twig, Hotwire (Turbo y Stimulus), Doctrine, PostgreSQL y Bulma.
Las versiones, dependencias y detalles arquitectónicos se concretarán y documentarán en `plan`.
Una desviación de esa pila requiere justificación técnica y acuerdo de ambos integrantes.

Los conceptos compartidos, sus identificadores, estados y relaciones tendrán una definición común.
La información de un animal, persona o solicitud debe referenciarse de forma coherente entre
historias, sin modelos incompatibles mantenidos por cada integrante. Los cambios del modelo,
migraciones y contratos compartidos deben coordinarse y reflejarse en las specs afectadas.

El código seguirá las convenciones de Symfony y una configuración común de formato y nombres,
concretada en el plan. Se separarán presentación, reglas de negocio y persistencia, evitando
duplicar reglas en distintas historias. Las reglas se validarán en el servidor; las operaciones
relacionadas deben preservar la coherencia de los datos incluso si una operación falla o concurren
varias peticiones. Se elegirá la solución más sencilla que cumpla lo especificado.

### IV. Responsabilidad individual y coordinación del equipo

Cada integrante se responsabilizará de cuatro historias y recorrerá con ellas el ciclo completo.
El reparto nominal y las dependencias se documentarán al preparar las specs, antes de comenzar el
desarrollo individual, y quedarán trazables en documentos, tareas y commits. El otro integrante
actuará como revisor, sin impedir la colaboración en el trabajo.

La base común se mantendrá en `main` y cada historia se trabajará en su rama `spec-001` a `spec-008`.
Las carpetas del mismo nombre contienen documentos y no equivalen a ramas Git. Los cambios se
integrarán mediante una solicitud de incorporación revisada por el compañero, identificando la
historia, los criterios afectados y las comprobaciones realizadas. Los commits describirán cambios
concretos y conservarán la autoría y las iteraciones del trabajo.

Los cambios de decisiones comunes se acordarán entre ambos antes de incorporarlos y se coordinará
quién modifica los archivos compartidos. Cada integrante comunicará dependencias, bloqueos y estado
de sus historias; se evitarán ediciones simultáneas incompatibles del modelo o de sus migraciones.
En la revisión cruzada se comprobará al menos un criterio de cada historia del compañero.

### V. Calidad y cierre demostrables

Una historia solo se dará por resuelta cuando cumpla todos sus criterios de aceptación, sus
comprobaciones estén registradas, su documentación coincida con el código y respete la base común.
La verificación debe incluir el recorrido habitual, una entrada inválida o un caso límite y una
condición de permisos o de estado. Si una categoría no aplica, se justificará y sustituirá por otra
relevante para esa historia.

Se automatizarán las comprobaciones reproducibles de reglas de negocio y de integración entre
historias cuando corresponda; las verificaciones manuales tendrán pasos y resultados registrados.
Cada evidencia debe indicar precondiciones, datos de entrada, pasos, resultado esperado, resultado
observado y versión del código. Se repetirán las comprobaciones de las historias afectadas y de
sus dependencias después de cada corrección.

La interfaz de usuario estará en español, con campos etiquetados, mensajes comprensibles y estados
de éxito, error y ausencia de resultados cuando correspondan. Las comprobaciones incluirán su uso
en móvil y escritorio y la navegación básica por teclado. Las entradas se validarán en el servidor
y los errores no deben dejar cambios parciales en los datos.

Las demostraciones y pruebas utilizarán datos ficticios. No se guardarán credenciales ni datos
personales reales en el repositorio o sus evidencias. Los permisos se definirán a partir de los roles
y decisiones del sistema: la consulta pública sin registro no implica que las operaciones internas
sean públicas. No se afirmará aptitud para producción ni cumplimiento normativo sin verificación.

## Organización del código y la documentación

- `.specify/memory/constitution.md` es la única fuente de estas reglas comunes.
- Las carpetas `spec-001` a `spec-008` conservan las historias iniciales. Conforme al enunciado v1,
  los resultados del ciclo se alojan en `specs/spec-001` a `specs/spec-008`, con `spec.md` y `tasks.md`
  y los informes de cada fase cuando se elaboren. Los documentos identificarán responsable,
  criterios, dependencias y estado real del trabajo.
- El `plan.md` común definirá la arquitectura, el modelo de datos, los contratos y las convenciones
  de todo el sistema. Los detalles por historia, si se documentan aparte, deberán referenciar esa
  base y mantenerla coherente.
- El código de aplicación seguirá la estructura común de Symfony; no se creará una aplicación
  independiente por historia. Las carpetas de specs no son módulos de código por sí mismas.
- Las decisiones, informes y evidencias se conservarán en `docs/`, enlazados desde las historias o
  tareas correspondientes. La matriz de trazabilidad cubrirá las ocho historias.
- La documentación académica estará en español. Los conceptos tendrán nombres consistentes entre
  documentos y código; los nombres técnicos se acordarán en el plan.
- Las fichas Markdown y el documento centralizado LaTeX/PDF deben mantenerse sincronizados cuando
  cambien las historias. Un PDF actualizado se generará a partir del LaTeX guardado y se revisará.

## Flujo de desarrollo y revisión

La preparación conjunta seguirá el orden de la guía: historias → Constitution → ocho specs y
reparto acordado → plan y modelo compartidos → repositorio y ramas.

Para cada historia se aplicará `specify → clarify → plan → tasks → analyze → implement → converge`,
volviendo al paso necesario cuando se detecte una duda, incoherencia o diferencia de implementación.
`analyze` informa de problemas entre documentos; las correcciones se realizan después en sus
fuentes. `converge` contrasta el código y añade las tareas correctivas al final de `tasks.md`, sin
modificar directamente el código, la spec o el plan ni borrar las tareas anteriores. Las tareas
correctivas se ejecutan mediante `implement` y se vuelven a comprobar.

Tras disponer de la primera versión funcional se repetirá el refinamiento de los apartados 5.1 a
5.4 del enunciado general. Se conservarán una referencia inicial, las versiones de herramientas,
decisiones justificadas, informes iniciales y finales, tareas correctivas y evidencias del antes y
el después. Se ejecutarán al menos dos recorridos que conecten historias de distintos integrantes:
uno satisfactorio y otro con un error, permiso o estado relevante.

La memoria y el dossier permitirán seguir historia y criterio, hallazgo o decisión, herramienta,
documento o código afectado, tarea, commit, comprobación, responsable, revisor y estado. Solo se
atribuirán a las herramientas comandos y resultados realmente ejecutados. No se inventarán fallos
ni se añadirán tareas para aparentar iteración cuando no existan incidencias reales.

## Gobernanza

La Constitution concreta los acuerdos de trabajo dentro del alcance docente; no puede sustituir ni
rebajar requisitos de los enunciados. Ante contradicciones, se registrará su localización y la
interpretación justificada. Para el reparto se mantienen cuatro historias por persona, coherentes
con la guía específica y el apartado 4 del enunciado general, pese a sus referencias aisladas a dos.
Una aclaración posterior del docente debe reflejarse en los documentos afectados.

Antes de integrar una historia, su autor y revisor comprobarán el cumplimiento de estos principios.
Una excepción debe quedar justificada con su alcance, consecuencias y comprobación prevista, y
acordada por ambos. Si cambia un requisito docente, necesitará la aclaración o validación del docente.

Los cambios de esta Constitution deben documentar motivo, acuerdo y documentos afectados. Las
redefiniciones incompatibles de principios incrementarán la versión mayor; las reglas nuevas, la
menor; y las aclaraciones sin cambio de obligación, el parche. No se reescribirá el historial de
decisiones para presentar un acuerdo posterior como si hubiera existido desde el inicio.

La ratificación se registrará cuando ambos integrantes revisen el documento. Siguen pendientes de
esa preparación conjunta los nombres, el reparto nominal y la resolución de las ambigüedades de las
historias. Su ausencia no se sustituye por datos inventados en esta propuesta.

## Fuentes

- `SI-30-protectora-animales-descripcion.pdf`, páginas 1-2: dominio, alcance y pila propuesta.
- `guia_proyecto_SDD_parte_I.pdf`, páginas 1-2: Constitution, colaboración y secuencia de trabajo.
- `Practica-SDD-enunciado_v1_IR.pdf`, apartados 3-7: responsabilidad, refinamiento y entregables.
- Las ocho fichas `spec-001/historia.md` a `spec-008/historia.md`: contexto y preguntas pendientes.
- `docs/decisiones.md`: interpretación de las contradicciones del enunciado vigente y decisiones de esta iteración.

**Versión:** 1.1.0 | **Ratificación:** pendiente de revisión conjunta | **Última modificación:** 2026-11-05

Cambio 1.1.0: actualización de fuentes y ubicación specs/ por el enunciado vigente; el usuario autoriza el desarrollo de HU-01 a HU-04. La revisión conjunta y el reparto nominal no se atribuyen como realizados.
