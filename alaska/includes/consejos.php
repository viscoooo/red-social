<?php
/**
 * Base de conocimientos y funciones para generar consejos inteligentes.
 * Estructura jerárquica: tipo_mascota -> tema -> { consejo, tips[], cuando_consultar_vet }
 * Incluye categoría 'general' para temas aplicables a cualquier especie.
 */

/**
 * Devuelve la matriz completa de conocimiento en memoria.
 * NOTA: Si crece mucho podría cargarse desde BD o JSON.
 */
function getConsejosBaseConocimiento(): array {
    return [
        'perro' => [
            'ansiedad' => [
                'consejo' => 'Establece una rutina diaria consistente. Los perros ansiosos se sienten más seguros con horarios predecibles para comer, pasear y dormir.',
                'tips' => [
                    'Usa juguetes interactivos que mantengan ocupado a tu perro',
                    'Practica ejercicios de relajación como masajes suaves',
                    'Considera difusores de feromonas calmantes'
                ],
                'cuando_consultar_vet' => 'Si la ansiedad causa destrucción extrema, autolesiones o no mejora en 2 semanas'
            ],
            'entrenamiento' => [
                'consejo' => 'Utiliza refuerzo positivo con premios pequeños y sesiones cortas de 5-10 minutos varias veces al día.',
                'tips' => [
                    'Sé consistente con las órdenes y gestos',
                    'Entrena en ambientes sin distracciones al principio',
                    'Termina siempre con un ejercicio que tu perro pueda hacer bien'
                ],
                'cuando_consultar_vet' => 'Si tu perro muestra agresión durante el entrenamiento'
            ],
            'alimentacion' => [
                'consejo' => 'Divide la ración diaria en 2-3 comidas y evita alimentos humanos, especialmente chocolate, uvas y cebollas.',
                'tips' => [
                    'Mide siempre la comida para evitar el sobrepeso',
                    'Cambia de alimento gradualmente en 7-10 días',
                    'Asegúrate de que siempre tenga agua fresca disponible'
                ],
                'cuando_consultar_vet' => 'Si hay vómitos, diarrea o pérdida de apetito por más de 24 horas'
            ],
            'salud' => [
                'consejo' => 'Las revisiones veterinarias anuales son esenciales, incluso si tu perro parece saludable.',
                'tips' => [
                    'Vacunación y desparasitación regular',
                    'Cepillado dental semanal',
                    'Control de pulgas y garrapatas todo el año'
                ],
                'cuando_consultar_vet' => 'Inmediatamente ante cualquier cambio en comportamiento, apetito o energía'
            ]
        ],
        'gato' => [
            'estrés' => [
                'consejo' => 'Los gatos estresados necesitan escondites seguros y territorios verticales. Proporciona rascadores, estantes altos y cajas cerradas.',
                'tips' => [
                    'Mantén la bandeja de arena limpia (1 por gato + 1 extra)',
                    'Usa difusores de feromonas Feliway',
                    'Evita cambios bruscos en el entorno'
                ],
                'cuando_consultar_vet' => 'Si hay marcaje con orina fuera de la bandeja o agresión repentina'
            ],
            'alimentacion' => [
                'consejo' => 'Los gatos son carnívoros obligados. Asegúrate de que su alimento contenga proteína animal de alta calidad.',
                'tips' => [
                    'Divide las comidas en porciones pequeñas a lo largo del día',
                    'Evita la leche de vaca (causa diarrea)',
                    'Proporciona agua corriente o fuentes para gatos'
                ],
                'cuando_consultar_vet' => 'Si hay vómitos frecuentes, especialmente con bilis (color amarillo)'
            ],
            'socializacion' => [
                'consejo' => 'Los gatos necesitan interacción diaria, pero respetando sus límites. Juega con ellos al menos 15 minutos al día.',
                'tips' => [
                    'Usa juguetes que imiten presas (plumas, ratones)',
                    'Nunca castigues, recompensa el buen comportamiento',
                    'Dedica tiempo individual a cada gato en casas con múltiples gatos'
                ],
                'cuando_consultar_vet' => 'Si hay aislamiento extremo o agresión hacia humanos'
            ]
        ],
        'general' => [
            'adopcion' => [
                'consejo' => 'Adoptar es una responsabilidad de por vida. Asegúrate de tener tiempo, recursos y compromiso antes de adoptar.',
                'tips' => [
                    'Visita el refugio varias veces antes de decidir',
                    'Pregunta sobre el historial médico y comportamiento',
                    'Prepara tu hogar con todo lo necesario antes de traer a tu nueva mascota'
                ],
                'cuando_consultar_vet' => 'Dentro de los primeros 7 días de adopción para revisión completa'
            ],
            'emergencia' => [
                'consejo' => 'En emergencias, mantén la calma y contacta inmediatamente a tu veterinario o clínica de emergencia más cercana.',
                'tips' => [
                    'Ten siempre a mano el número de emergencia de tu veterinario',
                    'Aprende primeros auxilios básicos para mascotas',
                    'Mantén un kit de emergencia con vendas, antiséptico y toalla'
                ],
                'cuando_consultar_vet' => 'INMEDIATAMENTE ante cualquier emergencia'
            ]
        ]
    ];
}

/**
 * Genera un consejo según tipo de mascota y problema.
 * Fallback 1: Busca en la especie solicitada.
 * Fallback 2: Busca en categoría 'general'.
 * Fallback 3: Devuelve plantilla genérica recomendando consulta veterinaria.
 */
function generarConsejoInteligente(string $tipo_mascota, string $problema): array {
    $consejos = getConsejosBaseConocimiento();
    $tipo_mascota = strtolower(trim($tipo_mascota));
    $problema = strtolower(trim($problema));

    if (isset($consejos[$tipo_mascota][$problema])) {
        return $consejos[$tipo_mascota][$problema];
    }
    if (isset($consejos['general'][$problema])) {
        return $consejos['general'][$problema];
    }

    return [
        'consejo' => 'Consulta con un veterinario especializado en ' . htmlspecialchars($tipo_mascota) . ' para obtener asesoramiento personalizado sobre ' . htmlspecialchars($problema) . '.',
        'tips' => [
            'Documenta los síntomas o comportamientos',
            'Toma fotos o videos si es posible',
            'No administres medicamentos humanos sin supervisión veterinaria'
        ],
        'cuando_consultar_vet' => 'Lo antes posible para una evaluación profesional'
    ];
}
