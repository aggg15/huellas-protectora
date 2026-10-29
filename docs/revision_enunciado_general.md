# Revisión de las historias frente al enunciado general

Revisión documental de las ocho historias iniciales y corrección de compatibilidad del LaTeX. Se conserva el alcance solicitado: definir las historias, no desarrollar todavía las fases posteriores de la práctica.

## Fuentes contrastadas

- `Practica-SDD-enunciado IR.pdf`, páginas 1-7: objetivos, reparto, proceso SDD, refinamiento y entregables.
- `guia_proyecto_SDD_parte_I.pdf`, páginas 1-2: ocho historias, Constitution, ocho specs, plan y modelo compartidos, reparto de cuatro por persona y ramas.
- `SI-30-protectora-animales-descripcion.pdf`, páginas 1-2: necesidades funcionales de Huellas.
- Las ocho fichas `spec-001/historia.md` a `spec-008/historia.md`, `historias_usuario.tex` y su PDF centralizado.

## Resultado

Las ocho historias cubren los procesos de la narrativa y son una base coherente para la fase inicial de identificación. Hay 38 criterios de aceptación iniciales con identificadores propios y referencias a su origen. Las preguntas abiertas se mantienen como decisiones pendientes, sin atribuir sus respuestas al enunciado.

Esto no acredita el cumplimiento de la entrega completa: faltan las especificaciones completas, los acuerdos y fases posteriores que se detallan más abajo. No se han ejecutado comandos de Spec Kit ni pruebas de una aplicación inexistente.

## Cobertura funcional de la narrativa

| Historia | Necesidades recogidas | Resultado de la revisión |
|---|---|---|
| HU-01 | Ficha, código propio, especie, edad aproximada, procedencia, fotografías, cuarentena de diez días y revisión veterinaria | Cubiertas. El registro de la revisión se identifica como concreción derivada. Pendientes el cómputo temporal, los datos mínimos de revisión y la transición a disponible. |
| HU-02 | Catálogo sin registro, filtros por especie, tamaño y edad, visibilidad de animales realmente en adopción y señal de acogida | Cubiertas. Pendientes las categorías de filtros y el detalle público. |
| HU-03 | Solicitud, cuestionario completo y máximo de dos solicitudes abiertas por persona | Cubiertas. Pendientes la identificación de persona, los estados que cuentan como abiertos y los campos del cuestionario. |
| HU-04 | Revisión, entrevista, rechazo motivado, aprobación, reserva y espera de las demás solicitudes | Cubiertas. Pendientes los datos de entrevista y la resolución de reservas fallidas y solicitudes en espera. |
| HU-05 | Entrega, contrato, abono de cuota, animal adoptado y dos seguimientos al mes y a los seis meses | Cubiertas. Pendientes el contrato, la cuota, el cómputo de fechas y la coordinación con otros procesos. |
| HU-06 | Disponibilidad y capacidad de casas, acogida temporal, continuidad de adopción y novedades de carácter y evolución | Cubiertas. Pendientes responsables, periodos, cierre de acogida y visibilidad de novedades. |
| HU-07 | Turnos de paseo y limpieza, inscripción libre, mínimos, máximos y destacado a cuarenta y ocho horas | Cubiertas. Pendientes la gestión de turnos, bajas, coincidencias y duración del destacado. |
| HU-08 | Aportación mensual mínima de diez euros, animal concreto, recepción de novedades y elección de continuidad tras la adopción | Cubiertas. Pendientes el canal, la gestión de aportaciones y las reglas de los vínculos de apadrinamiento. |

No se incorporan la contabilidad general ni las campañas de recogida de pienso, expresamente excluidas. Tampoco se convierten en requisitos obligatorios pasarelas de pago, servicios externos de mensajería o firma electrónica que la narrativa no prescribe.

## Contraste con la forma de trabajo y los entregables

| Exigencia y referencia | Situación actual | Trabajo posterior necesario |
|---|---|---|
| Ocho historias identificadas: enunciado, apartados 1-2; guía, paso 1 | Ocho historias HU-01 a HU-08, cada una en su carpeta y en el documento común | Revisión conjunta del equipo. |
| Especificar el qué y el porqué con reglas y aceptación: enunciado, apartado 3 | Roles, necesidades, beneficio, alcance, aceptación inicial y ambigüedades documentados | Elaborar las ocho specs completas tras acordar la Constitution y resolver decisiones. |
| Responsabilidad individual trazable: enunciado, apartado 4 y normas generales; guía, paso 2.2 | Responsable pendiente de acuerdo en las ocho fichas | Identificar a ambos integrantes y acordar cuatro historias por persona antes del desarrollo; reflejarlo en documentos, tareas y commits. No se inventan nombres ni un reparto ya aceptado. |
| Constitution: guía, paso 2.1 | Pendiente | Acordar calidad, organización del código y colaboración. |
| Plan y modelo compartidos: enunciado, apartado 3; guía, paso 2.3 | Pila propuesta y dependencias funcionales recogidas, sin diseño técnico cerrado | Acordar arquitectura, plan, modelo de datos y contratos compartidos. |
| Repositorio y ramas: enunciado, apartados 4 y 6; guía, paso 2.4 | Ocho carpetas documentales; no equivalen a ramas | Crear repositorio, base común en main y ramas spec-001 a spec-008 con autoría trazable. |
| Tareas, implementación e iteraciones: enunciado, apartados 3 y 5 | Pendientes, conforme al alcance actual | Derivar tareas trazables e implementar y verificar los criterios; conservar las iteraciones. |
| Clarify, analyze y converge: enunciado, apartados 5.1-5.3 | Preguntas funcionales preparadas; no se afirma haber ejecutado las herramientas | Conservar referencia inicial y versión de herramienta; registrar decisiones justificadas, informes iniciales y finales y tareas correctivas con evidencias. |
| Verificación integrada y revisión cruzada: enunciado, apartados 5.1-5.4 | Pendientes hasta disponer de aplicación | Revisar recorrido habitual, entrada inválida o caso límite y permisos o estado por historia; justificar sustituciones no aplicables. Revisar criterios del compañero, construir la matriz de trazabilidad y ejecutar al menos dos recorridos entre historias de distintos integrantes, uno satisfactorio y otro con error, permiso o estado. |
| Entrega final: enunciado, apartado 6 | El documento de historias es solo una parte inicial | Completar specs, plan, tareas, repositorio, memoria, demostración y dossier de refinamiento con responsables, revisores, commits y resultados. |

## Contradicción interna del material docente

El enunciado general establece cuatro historias por integrante en el apartado 4 y vuelve a indicar un reparto de ocho historias entre dos personas. Otros pasajes conservan referencias a dos historias por persona, incluida la tabla del apartado 5 y varios textos de defensa o revisión.

Se mantiene el criterio de cuatro historias por integrante porque coincide con esas instrucciones expresas del enunciado y con la guía específica, que fija ocho historias y cuatro por persona de forma consistente. El refinamiento y la defensa se prepararán sobre las cuatro asignadas, sin reducir la cobertura por las referencias contradictorias a dos. Esta interpretación se documenta para poder contrastarla con el docente si emite una aclaración.

## Ajustes realizados en esta revisión

1. En CA-01-01 se sustituye «Con los datos» por «Dados los datos» para mantener la formulación Dado/Cuando/Entonces.
2. En CA-01-04 se sustituye «puede quedar reflejada» por un resultado observable: al registrar la revisión, esta queda asociada a la ficha. Se mantiene explícita la necesidad de aclarar los datos mínimos y su carácter de concreción derivada; no se introduce un historial clínico.
3. Ambos ajustes se aplican en la ficha de HU-01 y en el LaTeX centralizado. Se conservan los ocho identificadores de historia y los 38 criterios.

## Corrección de representación en LaTeX

- Se usa codificación de fuentes `OT1` en lugar de `T1`, manteniendo fuentes vectoriales Latin Modern disponibles. Es una medida de compatibilidad; no se afirma haber identificado de forma concluyente el origen del fallo de la vista previa comunicado por el usuario.
- Los signos de apertura de interrogación se expresan como `\textquestiondown{}` para que LaTeX los resuelva explícitamente.
- Los identificadores se representan mediante `\HU{01}` a `\HU{08}`, definidos como texto indivisible `HU-XX`; se evita partir el código de historia en un cambio de línea.
- Se conserva UTF-8 para el texto español y se regenera el PDF desde el mismo LaTeX entregado.

## Verificación del documento

- Compilación satisfactoria con el compilador integrado del editor.
- Compilación con `pdflatex` en dos pasadas, sin advertencias finales, referencias pendientes ni desbordamientos.
- PDF final de diez páginas, con las ocho historias y los 38 criterios identificados.
- Comprobación de 35 signos `¿` y ocho referencias literales a `HU-08` en el texto extraído, sin la secuencia `uº`.
- Revisión visual de las páginas renderizadas y de los ejemplos con interrogaciones y HU-08. La comprobación visual se refiere al PDF exportado; la herramienta del editor confirma la compilación, pero no devuelve una captura de su vista previa.
- El PDF anterior ya conservaba esos caracteres en el texto extraído; la corrección aporta una representación explícita y una configuración de fuentes alternativa para la compilación comunicada por el usuario.
