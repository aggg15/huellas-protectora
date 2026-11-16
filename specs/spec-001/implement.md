# Implement · spec-001

Implementadas entidades Animal, AnimalPhoto, VeterinaryReview y User; AnimalService y PhotoStorage; acceso por roles; Twig/Bulma y previsualización Stimulus; migraciones y PostgreSQL local.

Comprobado con `vendor/bin/phpunit --filter Spec001 --testdox`: 7 pruebas, 32 aserciones correctas. Las respuestas 403/404 esperadas aparecen en el log como errores HTTP comprobados; no son fallos de PHPUnit.
