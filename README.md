# Huellas · Gestión de adopciones

Definición inicial de las ocho historias de la Práctica Grupal, a partir de la narrativa SI-30 y de la guía SDD, parte I. Fecha: 15 de octubre de 2026.

## Repositorio y colaboración

El proyecto dispone de un repositorio Git local con rama principal `main`. La base documental
incluye las ocho historias, la Constitution, las fuentes del enunciado, el informe de revisión
y el documento centralizado en LaTeX y PDF. La publicación en GitHub está pendiente de completar.

Las ramas `spec-001` a `spec-008` se crearán al preparar la base común de especificaciones y
acordar el reparto de cuatro historias por persona, conforme a la guía. Las carpetas existentes
organizan la documentación y no sustituyen esas ramas.

El archivo `.gitignore` excluye secretos, dependencias, cachés y auxiliares de LaTeX; las fuentes
`.tex` y los PDF entregables se versionan. La Constitution define la revisión entre compañeros
y la trazabilidad de los cambios.

## Constitution

La [Constitution de Huellas](.specify/memory/constitution.md), versión 1.0.0 preparada el
29 de octubre de 2026, define los principios de alcance, trazabilidad, diseño compartido,
colaboración y calidad. Incluye la organización documental, el flujo SDD y las reglas para
modificar los acuerdos. Está preparada para la revisión conjunta de los dos integrantes;
la ratificación y el reparto nominal siguen pendientes. Su redacción no acredita haber
ejecutado `/speckit.constitution` ni haber inicializado la integración de Spec Kit.

## Historias

| Historia | Contenido | Documento |
|---|---|---|
| HU-01 | Registrar un animal y gestionar su cuarentena | [spec-001/historia.md](spec-001/historia.md) |
| HU-02 | Consultar y filtrar el catálogo público de adopción | [spec-002/historia.md](spec-002/historia.md) |
| HU-03 | Enviar una solicitud de adopción completa | [spec-003/historia.md](spec-003/historia.md) |
| HU-04 | Evaluar solicitudes, entrevistar y reservar el animal | [spec-004/historia.md](spec-004/historia.md) |
| HU-05 | Formalizar la adopción y programar los seguimientos | [spec-005/historia.md](spec-005/historia.md) |
| HU-06 | Gestionar casas de acogida y la evolución del animal | [spec-006/historia.md](spec-006/historia.md) |
| HU-07 | Inscribirse en turnos y detectar falta de voluntarios | [spec-007/historia.md](spec-007/historia.md) |
| HU-08 | Apadrinar un animal y recibir sus novedades | [spec-008/historia.md](spec-008/historia.md) |

## Documento centralizado

- PDF: [output/pdf/historias_usuario.pdf](output/pdf/historias_usuario.pdf).
- LaTeX editable y autónomo: [historias_usuario.tex](historias_usuario.tex). Contiene las ocho historias completas y no necesita archivos auxiliares del proyecto.

Cada ficha y el documento común incluyen la misma historia, alcance, criterios de aceptación, dependencias, dudas para `clarify` y límites. Los criterios `CA-XX-YY` identifican la historia y el criterio, y señalan su origen en la narrativa. Si se modifica una historia en Markdown o en LaTeX, debe reflejarse el cambio en la otra representación antes de volver a distribuir el PDF.

Para regenerar el PDF desde la raíz del proyecto, con una distribución LaTeX instalada, ejecutar dos veces:

```sh
pdflatex -interaction=nonstopmode -halt-on-error -output-directory=output/pdf historias_usuario.tex
pdflatex -interaction=nonstopmode -halt-on-error -output-directory=output/pdf historias_usuario.tex
```

El editor LaTeX integrado de Codex también permite editar el documento y previsualizar su compilación.

El LaTeX utiliza fuentes OT1/Latin Modern, `\textquestiondown{}` para los signos de apertura de interrogación y la macro `\HU{08}` (análoga para las demás historias) para mostrar identificadores indivisibles como HU-08.

## Revisión frente al enunciado general

El [informe de revisión](docs/revision_enunciado_general.md) contrasta las ocho historias con la narrativa, la guía y los entregables del enunciado general. Distingue lo preparado para esta fase de lo pendiente para completar la práctica, documenta la discrepancia del reparto y recoge las comprobaciones de la corrección tipográfica.

## Estado dentro de la guía

Se ha realizado la definición inicial de historias (paso 1), preparado la Constitution (paso 2.1), ambas para revisión conjunta, e inicializado el repositorio Git local en `main`. Estas fichas no son las especificaciones funcionales completas. Siguen pendientes el trabajo con `specify`, el reparto acordado de cuatro historias por persona, el plan y el modelo de datos compartidos, la publicación en GitHub y las ramas por historia. Las carpetas `spec-001` a `spec-008` son organización documental; no se han creado las ramas por spec ni se han asignado responsables.

Cada historia deberá seguir `specify → clarify → plan → tasks → analyze → implement → converge`, repitiendo los pasos necesarios y verificando los criterios de aceptación. Las dudas se han conservado explícitas para no atribuir a la narrativa decisiones que no contiene. No se presenta este trabajo como ejecución de comandos de Spec Kit.

## Discrepancia documental detectada

`Practica-SDD-enunciado IR.pdf` establece cuatro historias por integrante en el apartado 4, pero conserva referencias a dos en otros pasajes. Se mantiene el criterio explícito y coherente de la guía específica: ocho historias, cuatro por persona. Los apartados 5.1 a 5.4 del enunciado general exigen además repetir el refinamiento sobre la primera versión funcional, con decisiones justificadas, informes, trazabilidad, evidencias del antes y el después y revisión entre compañeros. El ciclo previo de la guía no sustituye ese trabajo posterior.

## Fuentes

- `SI-30-protectora-animales-descripcion.pdf`: necesidades y pila propuesta (Symfony, Twig, Hotwire, Doctrine, PostgreSQL y Bulma).
- `guia_proyecto_SDD_parte_I.pdf`: metodología, ocho historias, reparto y colaboración.
- `Practica-SDD-enunciado IR.pdf`: objetivos generales, entregables y evidencias del refinamiento.
- Práctica 1: contexto académico sobre especificación previa, trazabilidad y verificación; sin trasladar al nuevo dominio sus reglas del HIS ni su pila tecnológica.

La contabilidad general de la asociación y las campañas de recogida de pienso están fuera del encargo.
