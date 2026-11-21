# Evidencias de la revisión previa a la entrega (2026-11-21)

Ejecuciones reales en Docker (`php:8.3-cli` con gd, pdo_pgsql, mbstring e intl; `postgres:16-alpine`), con los mismos pasos que `.github/workflows/ci.yml`.

| Evidencia | Código | Resultado |
|---|---|---|
| [ci-main-antes/](ci-main-antes/) | `main` a74f85f, antes de la revisión | 112 pruebas, 1301 aserciones, OK; esquema sincronizado |
| [hu03-hu04-pruebas-nuevas-sobre-codigo-anterior.txt](hu03-hu04-pruebas-nuevas-sobre-codigo-anterior.txt) | Pruebas nuevas de HU-03/HU-04 sobre `src/` y `templates/` anteriores | 21 pruebas: 1 error y 16 fallos (el «antes» de C1-01…C1-12) |
| [hu03-hu04-suite-tras-correccion.txt](hu03-hu04-suite-tras-correccion.txt) | Rama de corrección de HU-03/HU-04 | 129 pruebas, 1487 aserciones, OK |
| [hu05-hu07-hu08-pruebas-nuevas-sobre-codigo-anterior.txt](hu05-hu07-hu08-pruebas-nuevas-sobre-codigo-anterior.txt) | Pruebas nuevas de HU-05/07/08 sobre el código anterior | Fallos de las pruebas nuevas (el «antes» de C2-01…C2-08) |
| [hu05-hu07-hu08-suite-tras-correccion.txt](hu05-hu07-hu08-suite-tras-correccion.txt) | Rama de corrección de HU-05/07/08 | 117 pruebas, 1376 aserciones, OK |
| [ci-rama-integrada/](ci-rama-integrada/) | `revision-entrega` con las cinco ramas fusionadas (76223b2) | Todos los pasos OK; 144 pruebas, 1659 aserciones |

Después de esa ejecución se aplicaron D45 (el personal no solicita adopciones; T312) y el texto «Animales liberados: N»;
`Spec002`, `Spec003` y `Spec008` se volvieron a ejecutar (49 pruebas OK; Spec003: 19 pruebas OK). Suite completa final sobre el código definitivo: [suite-final.txt](suite-final.txt), **OK (145 tests, 1680 assertions)**.
Los intentos previos ([1](suite-final-intento-1.txt), [2](suite-final-intento-2.txt)) fallaron en una prueba cada uno: el 2 destapó T727, un defecto real
de HU-07 que solo se manifestaba después de las 21:00; el 1 fue un fallo intermitente de un proceso auxiliar de carrera.

De la rama de HU-01/HU-02 (C1-05…C1-20) no se conservó el registro del «antes»; su resultado posterior está incluido en la
ejecución integrada. Las líneas `[error] Uncaught PHP Exception…` de los registros corresponden a pruebas que comprueban
respuestas 403/404/409 y no son fallos.
