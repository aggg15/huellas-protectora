# Especificación HU-02 · spec-002

Fecha: 2026-11-16.
Historia: HU-02 · Consultar y filtrar el catálogo público de adopción.
Responsable GitHub: `abcb5-ua` (autoría de la PR #5); revisión: `aggg15` (aprobación de la PR #5).
Referencia base: `SI-30-protectora-animales-descripcion.pdf` (pág. 1), `plan.md`, `data-model.md` y `docs/decisiones.md`.

## Historia de usuario

Como persona interesada en adoptar, quiero consultar sin registrarme los animales que buscan hogar y filtrarlos por especie, tamaño y edad para encontrar uno que encaje con mi situación.

## Alcance de la historia

- Consulta pública sin registro de los animales que están realmente disponibles para adopción (`status = 'available'`).
- Filtros de búsqueda por especie (`dog`, `cat`), tamaño (`small`, `medium`, `large`) y edad (`<12`, `12-83`, `>=84`), combinables mediante conjunción lógica (AND).
- Visibilidad normal en el catálogo de los animales que se encuentren en régimen de casa de acogida (`fostered = true`) pero sigan disponibles para adopción, incorporando una indicación visual explícita de su condición.
- Exclusión estricta de animales en cuarentena (`quarantine`), reservados (`reserved`) o adoptados (`adopted`), tanto en el listado del catálogo como en las fichas públicas directas (`HTTP 404`).
- Promoción automática previa de animales con cuarentena vencida (≥ 240 horas UTC) al acceder a la consulta pública.

---

## Criterios de aceptación y escenarios Gherkin

### CA-02-01: Consulta pública sin registro
Dado que existen animales dados de alta con estado disponible (`available`),
cuando cualquier persona accede a la dirección web pública del catálogo (`/animales`) sin haber iniciado sesión ni registrarse,
entonces puede visualizar la lista de animales disponibles con su fotografía principal, código identificativo, especie, edad aproximada, tamaño y procedencia.

#### Escenario 1.1: Listado público anónimo exitoso
- **Dado** que existen 2 animales en estado `available` en el sistema,
- **Cuando** un usuario anónimo realiza una petición `GET /animales`,
- **Entonces** la respuesta tiene código de estado HTTP 200,
- **Y** la página muestra las tarjetas de los 2 animales disponibles,
- **Y** no se solicita ninguna autenticación ni redirección a `/acceso`.

---

### CA-02-02: Filtros por especie, tamaño y edad
Dados animales disponibles con diversas especies, tamaños y edades,
cuando una persona aplica uno o varios filtros en el formulario de búsqueda,
entonces se muestran únicamente los animales disponibles que satisfacen simultáneamente todos los criterios seleccionados (conjunción AND).

#### Escenario 2.1: Filtro por especie
- **Dado** un perro disponible y un gato disponible,
- **Cuando** se envía `GET /animales?species=cat`,
- **Entonces** solo se muestra el gato en el catálogo y se excluye el perro.

#### Escenario 2.2: Filtro por tamaño y edad combinados
- **Dado** un perro cachorro pequeño (6 meses), un perro adulto mediano (24 meses) y un perro senior pequeño (96 meses),
- **Cuando** se envía `GET /animales?species=dog&size=small&age=<12`,
- **Entonces** solo se muestra el perro cachorro pequeño de 6 meses.

#### Escenario 2.3: Filtro sin coincidencias
- **Dado** un catálogo sin animales que cumplan el filtro `species=cat&size=large`,
- **Cuando** se aplica dicha búsqueda,
- **Entonces** se muestra un mensaje informativo indicando que no hay animales disponibles con esos criterios y un enlace para restablecer los filtros.

---

### CA-02-03: Exclusión de animales en cuarentena, reservados o adoptados
Dados animales registrados en el sistema en estado `quarantine`, `reserved` o `adopted`,
cuando cualquier persona consulta el catálogo público o intenta acceder a la ficha individual de dicho animal (`/animales/{id}`),
entonces esos animales no aparecen en el catálogo y la petición a su ficha individual responde con un código de error HTTP 404 No Encontrado.

#### Escenario 3.1: Exclusión del catálogo
- **Dado** un animal en cuarentena, un animal reservado y un animal adoptado,
- **Cuando** se accede a `GET /animales`,
- **Entonces** ninguno de esos tres animales figura en los resultados del catálogo.

#### Escenario 3.2: Ficha pública de animal no disponible responde 404
- **Dado** un animal con estado `quarantine` y UUID `0193339c-8500-7000-8000-000000000001`,
- **Cuando** un usuario anónimo solicita `GET /animales/0193339c-8500-7000-8000-000000000001`,
- **Entonces** el servidor responde con estado HTTP 404,
- **Y** las fotografías asociadas a dicho animal en `/fotos/{fotoId}` también responden 404.

---

### CA-02-04: Animal en acogida disponible visible con indicación
Dado un animal con estado `available` que se encuentra en una casa de acogida (`fostered = true`),
cuando se consulta el catálogo público o su ficha pública,
entonces continúa visible entre los animales disponibles para adopción y se muestra un distintivo visual claro ("En casa de acogida").

#### Escenario 4.1: Distintivo en el catálogo
- **Dado** un animal disponible con `fostered = true`,
- **Cuando** se consulta `GET /animales`,
- **Entonces** el animal aparece listado y su tarjeta contiene el distintivo "En casa de acogida".

#### Escenario 4.2: Distintivo en la ficha pública
- **Dado** un animal disponible con `fostered = true`,
- **Cuando** se accede a `GET /animales/{id}`,
- **Entonces** la página de detalle muestra la información del animal y destaca que está en casa de acogida temporal mientras espera adopción definitiva.

---

## Dependencias y coordinación

- **HU-01:** Utiliza la entidad `Animal`, su código `HUE-`, especie, edad en meses, tamaño, procedencia y fotos de `AnimalPhoto`. Se beneficia de la promoción automática de cuarentena cumplida (`releaseDueAnimals()`).
- **HU-04, HU-05 y HU-06:** Los estados de reserva (`reserved`) de HU-04 y adopción (`adopted`) de HU-05 se respetan excluyéndolos de la oferta pública. El campo `fostered` gestionado operativamente en HU-06 es visualizado de forma compatible.
- **HU-03:** Proporcionará el enlace desde la ficha pública para iniciar la solicitud de adopción.

## Límites

- El catálogo y las fichas son estrictamente de consulta de lectura.
- No se requiere inicio de sesión ni creación de cuenta para consultar.
- Las notas veterinarias y los datos de gestión interna del personal no se exponen públicamente.
