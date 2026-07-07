<?php
/* ============================================================
   BAMBOO SEGUROS · CATÁLOGO DE SEGUROS (fuente única de verdad)
   Alimenta: menú, selector del formulario, grilla de servicios,
   páginas /seguros/<slug>.php, sitemap.xml y el schema.
   'segmento': persona | pyme | ambos
   'icon': clave de bb_icon() en partials/icons.php
   ============================================================ */

$SEGUROS = [

  'auto' => [
    'slug' => 'auto',
    'nombre' => 'Seguro de Vehículo',
    'menu' => 'Vehículo',
    'segmento' => 'ambos',
    'icon' => 'car',
    'meta_title' => 'Cotizar Seguro Automotriz | Auto, Moto y Flotas — Bamboo Seguros',
    'meta_desc' => 'Cotiza tu seguro de auto comparando las aseguradoras del mercado con una corredora independiente. Full cobertura, terceros y flotas. Cotiza rápidamente.',
    'h1' => 'Seguro de Vehículo',
    'lead' => 'El seguro de vehículo protege tu auto, moto o flota frente a accidentes, robo y daños a terceros. Como corredora independiente, comparamos coberturas, deducibles y precios entre las aseguradoras del mercado para encontrar la opción que de verdad te conviene.',
    'coberturas' => [
      'Full cobertura (daños propios, robo y responsabilidad civil)',
      'Pérdida total y daños a terceros',
      'Auto de reemplazo y asistencia en ruta',
      'Cobertura para motos y vehículos comerciales',
      'Seguros de flotas para empresas',
    ],
    'para_quien' => 'Para autos, motos y flotas.',
    'para_quien_persona' => 'Para tu auto o moto particular.',
    'para_quien_pyme' => 'Para autos comerciales y flotas.',
    'faqs' => [
      ['q' => '¿Qué datos necesito para cotizar mi seguro de auto?', 'a' => 'Necesitas los datos del vehículo (marca, modelo, año y patente) y del propietario (RUT y comuna). Con eso comparamos coberturas y deducibles entre las aseguradoras y te enviamos las mejores opciones.'],
      ['q' => '¿Qué es el deducible en un seguro de auto?', 'a' => 'El deducible es el monto que pagas tú al usar el seguro en un siniestro; el resto lo cubre la compañía. A mayor deducible, menor prima. Te ayudamos a elegir el equilibrio que más te conviene.'],
    ],
  ],

  'vida' => [
    'slug' => 'vida',
    'nombre' => 'Seguro de Vida',
    'menu' => 'Vida',
    'segmento' => 'persona',
    'icon' => 'heart',
    'meta_title' => 'Seguro de Vida en Chile | Cotiza y Compara Coberturas — Bamboo',
    'meta_desc' => '¿Cuánto cuesta un seguro de vida? Comparamos primas en UF de las mejores compañías y te asesoramos para proteger a tu familia. Cotiza con Bamboo.',
    'h1' => 'Seguro de Vida',
    'lead' => 'El seguro de vida entrega un respaldo económico a tu familia o beneficiarios en caso de fallecimiento o invalidez. Comparamos primas y coberturas en UF entre las principales compañías para que protejas a los tuyos con tranquilidad.',
    'coberturas' => [
      'Seguro de vida temporal y con ahorro',
      'Cobertura por fallecimiento e invalidez',
      'Enfermedades graves',
      'Seguros de vida con ahorro (APV)',
    ],
    'para_quien' => 'Personas que quieren proteger a su familia y/o complementar su ahorro.',
    'faqs' => [
      ['q' => '¿Cuánto cuesta un seguro de vida en Chile?', 'a' => 'La prima depende de tu edad, estado de salud, monto asegurado y coberturas. Por eso comparamos varias compañías por ti: una misma cobertura puede tener precios muy distintos según la aseguradora.'],
    ],
  ],

  'salud-individual' => [
    'slug' => 'salud-individual',
    'nombre' => 'Seguro Complementario de Salud',
    'menu' => 'Salud complementaria',
    'segmento' => 'persona',
    'icon' => 'health',
    'meta_title' => 'Seguro Complementario de Salud | Cobertura Médica — Bamboo',
    'meta_desc' => 'Seguro de salud complementario: reembolso de gastos médicos y hospitalización sobre tu Isapre o Fonasa. Cotiza con Bamboo Seguros.',
    'h1' => 'Seguro Complementario de Salud',
    'lead' => 'El seguro complementario de salud reembolsa parte de los gastos médicos y hospitalarios que tu Isapre o Fonasa no cubre por completo: cubre el diferencial entre el gasto total y lo que bonifica tu sistema de salud. Comparamos coberturas entre las mejores compañías para que elijas la que se ajusta a ti.',
    'coberturas' => [
      'Reembolso de gastos médicos ambulatorios',
      'Hospitalización y cirugías',
      'Exámenes médicos',
      'Complemento sobre Isapre o Fonasa (cubre el diferencial)',
    ],
    'para_quien' => 'Personas que quieren reforzar su cobertura de salud.',
    'faqs' => [],
  ],

  'catastrofico-individual' => [
    'slug' => 'catastrofico-individual',
    'nombre' => 'Seguro Catastrófico Individual',
    'menu' => 'Catastrófico individual',
    'segmento' => 'persona',
    'icon' => 'umbrella',
    'meta_title' => 'Seguro Catastrófico de Salud Individual | Cotiza — Bamboo Seguros',
    'meta_desc' => 'Protege tu patrimonio ante enfermedades o tratamientos de alto costo. El seguro catastrófico cubre los gastos médicos mayores. Cotiza con Bamboo.',
    'h1' => 'Seguro Catastrófico de Salud Individual',
    'lead' => 'El seguro catastrófico te protege frente a enfermedades graves o tratamientos de alto costo —como los oncológicos— que tu plan de salud no alcanza a cubrir. Es la red de seguridad que evita que un evento médico mayor afecte tu patrimonio.',
    'coberturas' => [
      'Gastos médicos de alto costo (enfermedades o accidentes)',
    ],
    'para_quien' => 'Personas y familias que quieren protegerse ante gastos médicos mayores, incluyendo seguro oncológico.',
    'faqs' => [],
  ],

  'viaje' => [
    'slug' => 'viaje',
    'nombre' => 'Asistencia en Viaje',
    'menu' => 'Asistencia en viaje',
    'segmento' => 'persona',
    'icon' => 'plane',
    'meta_title' => 'Asistencia en Viaje y Schengen | Asistencia Internacional — Bamboo Seguros',
    'meta_desc' => 'Asistencia en viaje con cobertura Schengen (mínimo 30.000 €) y asistencia internacional. Comparamos compañías y te asesoramos. Cotiza antes de viajar.',
    'h1' => 'Asistencia en Viaje',
    'lead' => 'La asistencia en viaje cubre gastos médicos, asistencia y emergencias mientras estás fuera de Chile. Si viajas a Europa, te ayudamos con la cobertura Schengen obligatoria (mínimo 30.000 €). Cotiza antes de viajar y parte tranquilo.',
    'coberturas' => [
      'Asistencia médica internacional',
      'Cobertura Schengen (mínimo 30.000 €)',
      'Cancelación de viaje y pérdida de equipaje',
      'Asistencia 24/7 en el extranjero',
    ],
    'para_quien' => 'Personas y familias que viajan al extranjero.',
    'faqs' => [
      ['q' => '¿Es obligatorio el seguro de viaje para Europa?', 'a' => 'Sí. Para viajar al espacio Schengen se exige un seguro de viaje con cobertura médica mínima de 30.000 €. Te ayudamos a contratar una póliza que cumpla con ese requisito.'],
    ],
  ],

  'accidentes-personales' => [
    'slug' => 'accidentes-personales',
    'nombre' => 'Seguro de Accidentes Personales',
    'menu' => 'Accidentes personales',
    'segmento' => 'persona',
    'icon' => 'shield-user',
    'meta_title' => 'Seguro de Accidentes Personales | Gastos Médicos e Invalidez — Bamboo Seguros',
    'meta_desc' => 'Protección frente a accidentes con cobertura de gastos médicos, invalidez y muerte accidental. Comparamos compañías y te asesoramos. Cotiza hoy.',
    'h1' => 'Seguro de Accidentes Personales',
    'lead' => 'El seguro de accidentes personales cubre gastos médicos, invalidez o fallecimiento producto de un accidente. Es ideal como complemento para deportistas, trabajadores y escolares. Comparamos opciones y te asesoramos.',
    'coberturas' => [
      'Gastos médicos por accidente',
      'Invalidez total y parcial',
      'Muerte accidental',
      'Cobertura escolar y deportiva',
    ],
    'para_quien' => 'Personas en general, en sus distintas actividades.',
    'faqs' => [],
  ],

  'incendio-sismo' => [
    'slug' => 'incendio-sismo',
    'nombre' => 'Seguro de Incendio',
    'menu' => 'Incendio',
    'segmento' => 'ambos',
    'icon' => 'home',
    'meta_title' => 'Seguro de Incendio para tu Hogar | Cotiza — Bamboo Seguros',
    'meta_desc' => 'Protege tu casa contra incendio, sismo y robo. Comparamos coberturas de las mejores aseguradoras y te asesoramos. Cotiza tu seguro de hogar.',
    'h1' => 'Seguro de Incendio',
    'lead' => 'El seguro de incendio protege tu propiedad —casa, departamento, local comercial, condominio, restaurante, oficina, bodega o industria, con su estructura y contenido— frente a incendio, terremoto, robo y otros daños. En un país sísmico como Chile, es la base para resguardar tu patrimonio. Comparamos coberturas por ti.',
    'coberturas' => [
      'Incendio de estructura y contenido',
      'Sismo y terremoto',
      'Robo con fuerza',
      'Daños por agua y eventos de la naturaleza',
    ],
    'para_quien' => 'Propietarios, arrendatarios y comunidades.',
    'faqs' => [
      ['q' => '¿El seguro de incendio cubre terremotos?', 'a' => 'No siempre viene incluido: la cobertura de sismo suele contratarse de forma adicional. Te explicamos qué incluye cada póliza para que tu hogar quede realmente protegido ante un terremoto.'],
    ],
  ],

  'responsabilidad-civil' => [
    'slug' => 'responsabilidad-civil',
    'nombre' => 'Seguro de Responsabilidad Civil',
    'menu' => 'Responsabilidad civil',
    'segmento' => 'pyme',
    'icon' => 'scale',
    'meta_title' => 'Seguro de Responsabilidad Civil | Empresas y Pymes — Bamboo Seguros',
    'meta_desc' => 'Seguro de responsabilidad civil para tu empresa o pyme. Te protegemos frente a daños a terceros con asesoría independiente. Cotiza hoy.',
    'h1' => 'Seguro de Responsabilidad Civil',
    'lead' => 'El seguro de responsabilidad civil cubre los daños que tu empresa pueda causar a terceros —personas o bienes— durante su operación. Es clave para pymes que atienden público o prestan servicios. Comparamos coberturas entre aseguradoras por ti.',
    'coberturas' => [
      'RC general y de explotación',
      'RC de productos',
      'Daños a terceros y a sus bienes',
      'Gastos de defensa legal',
    ],
    'para_quien' => 'Pymes, empresas, profesionales y locales comerciales.',
    'faqs' => [],
  ],

  'garantia' => [
    'slug' => 'garantia',
    'nombre' => 'Seguro de Garantía',
    'menu' => 'Garantía',
    'segmento' => 'pyme',
    'icon' => 'document',
    'meta_title' => 'Seguro de Garantía y Pólizas para Licitaciones | Pymes — Bamboo',
    'meta_desc' => 'Pólizas de garantía para licitaciones: seriedad de la oferta, fiel cumplimiento y anticipo. Cotiza con Bamboo Seguros.',
    'h1' => 'Seguro de Garantía',
    'lead' => 'El seguro de garantía respalda el cumplimiento de obligaciones entre un contratista y el mandante: si el contratista no cumple, la compañía indemniza al mandante (Estado o privado).',
    'coberturas' => [
      'Seriedad de la oferta',
      'Fiel cumplimiento del contrato',
      'Correcta ejecución y anticipo',
      'Garantías para Mercado Público y licitaciones',
    ],
    'para_quien' => 'Contratistas, proveedores del Estado y pymes que participan en licitaciones.',
    'faqs' => [
      ['q' => '¿Qué conviene más: boleta de garantía o póliza de garantía?', 'a' => 'Depende del caso y de lo que exija la licitación o el contrato. Ambos instrumentos respaldan el cumplimiento de tus obligaciones ante el mandante. Te asesoramos para elegir el que mejor se ajuste a tu situación.'],
    ],
  ],

  'transporte' => [
    'slug' => 'transporte',
    'nombre' => 'Seguro de Transporte de Carga',
    'menu' => 'Transporte',
    'segmento' => 'pyme',
    'icon' => 'truck',
    'meta_title' => 'Seguro de Transporte de Carga | Empresas y Pymes — Bamboo Seguros',
    'meta_desc' => 'Protege tu mercadería en tránsito nacional e internacional contra pérdida y daños. Comparamos aseguradoras y te asesoramos. Cotiza con Bamboo.',
    'h1' => 'Seguro de Transporte de Carga',
    'lead' => 'El seguro de transporte protege tu mercadería mientras está en tránsito —terrestre, marítimo o aéreo— frente a pérdidas y daños. Esencial para pymes que mueven productos dentro o fuera de Chile. Comparamos coberturas por ti.',
    'coberturas' => [
      'Transporte terrestre, marítimo y aéreo',
      'Tránsito nacional e internacional',
      'Pérdida total y parcial de la carga',
      'Daños por manipulación y accidentes',
    ],
    'para_quien' => 'Importadores, exportadores y pymes.',
    'faqs' => [],
  ],

  'apv' => [
    'slug' => 'apv',
    'nombre' => 'APV — Ahorro Previsional Voluntario',
    'menu' => 'APV',
    'segmento' => 'persona',
    'icon' => 'piggy',
    'meta_title' => 'APV con Seguro | Ahorra y Rebaja Impuestos — Bamboo Seguros',
    'meta_desc' => 'El APV te permite mejorar tu pensión y rebajar impuestos. Comparamos alternativas de ahorro previsional voluntario con seguro. Cotiza con Bamboo.',
    'h1' => 'APV — Ahorro Previsional Voluntario',
    'lead' => 'El Ahorro Previsional Voluntario (APV) te permite complementar tu pensión y acceder a beneficios tributarios. A través de seguros con ahorro, te ayudamos a elegir la alternativa que mejor combina protección y rentabilidad.',
    'coberturas' => [
      'APV con beneficio tributario (Régimen A y B)',
      'Seguros de vida con ahorro',
      'Complemento de pensión',
      'Ahorro con cobertura de protección',
    ],
    'para_quien' => 'Trabajadores que quieren mejorar su pensión y rebajar impuestos.',
    'faqs' => [
      ['q' => '¿El APV rebaja impuestos?', 'a' => 'Sí. Según el régimen que elijas, el APV puede rebajar la base imponible de tus impuestos o entregar una bonificación fiscal. Te explicamos cuál régimen te conviene según tu situación.'],
    ],
  ],

  'ingenieria' => [
    'slug' => 'ingenieria',
    'nombre' => 'Seguro de Ingeniería y Construcción',
    'menu' => 'Ingeniería',
    'segmento' => 'pyme',
    'icon' => 'hardhat',
    'meta_title' => 'Seguro de Ingeniería y Construcción | Obras — Bamboo Seguros',
    'meta_desc' => 'Protege tus obras, maquinaria y montajes durante la construcción. Todo riesgo construcción y montaje. Asesoría independiente. Cotiza con Bamboo.',
    'h1' => 'Seguro de Ingeniería y Construcción',
    'lead' => 'El seguro de ingeniería protege obras, maquinaria y montajes durante su construcción o instalación frente a daños y accidentes. Es fundamental para constructoras y empresas de proyectos. Comparamos coberturas todo riesgo por ti.',
    'coberturas' => [
      'Todo riesgo construcción (CAR)',
      'Todo riesgo montaje (EAR)',
      'Avería de maquinaria',
      'Equipos y maquinaria de contratistas',
    ],
    'para_quien' => 'Constructoras, contratistas y empresas de proyectos.',
    'faqs' => [],
  ],

  'rc-administradores' => [
    'slug' => 'rc-administradores',
    'nombre' => 'Responsabilidad Civil de Administradores y Directores (D&O)',
    'menu' => 'Responsabilidad civil administradores',
    'segmento' => 'pyme',
    'icon' => 'briefcase',
    'meta_title' => 'Seguro de Administradores y Directores (D&O) — Bamboo Seguros',
    'meta_desc' => 'Protege el patrimonio de directores y administradores frente a reclamos por sus decisiones de gestión. Asesoría independiente. Cotiza con Bamboo.',
    'h1' => 'Responsabilidad Civil de Administradores y Directores',
    'lead' => 'El seguro de administradores y directores (D&O) protege el patrimonio personal de quienes dirigen una empresa frente a reclamos por decisiones de gestión. Comparamos coberturas entre aseguradoras para resguardar a tu directorio.',
    'coberturas' => [
      'Responsabilidad de directores y gerentes',
      'Gastos de defensa legal',
      'Reclamos de terceros y socios',
      'Protección del patrimonio personal del directivo',
    ],
    'para_quien' => 'Directores, comité y administradores de condominios.',
    'faqs' => [],
  ],

  'salud-empresas' => [
    'slug' => 'salud-empresas',
    'nombre' => 'Seguro de Salud Colectivo (Empresas)',
    'menu' => 'Salud empresas',
    'segmento' => 'pyme',
    'icon' => 'health',
    'meta_title' => 'Seguro de Salud Colectivo para Empresas | Bamboo Seguros',
    'meta_desc' => 'Seguro de salud colectivo para tu empresa: cobertura médica y hospitalaria para tus colaboradores. Asesoría independiente. Cotiza con Bamboo.',
    'h1' => 'Seguro de Salud Colectivo para Empresas',
    'lead' => 'El seguro de salud colectivo es un beneficio para tus colaboradores: cobertura médica y hospitalaria a precios convenientes por ser grupal. Te ayudamos a diseñar el plan que mejor se ajusta a tu empresa.',
    'coberturas' => [
      'Cobertura médica y hospitalaria grupal',
      'Beneficio para colaboradores',
      'Planes a la medida de la empresa',
    ],
    'para_quien' => 'Empresas y pymes que quieren ofrecer salud a su equipo.',
    'faqs' => [],
  ],

  'catastrofico-empresas' => [
    'slug' => 'catastrofico-empresas',
    'nombre' => 'Seguro Catastrófico Colectivo (Empresas)',
    'menu' => 'Catastrófico empresas',
    'segmento' => 'pyme',
    'icon' => 'umbrella',
    'meta_title' => 'Seguro Catastrófico Colectivo para Empresas | Bamboo Seguros',
    'meta_desc' => 'Cobertura catastrófica de salud para los colaboradores de tu empresa ante enfermedades y tratamientos de alto costo. Cotiza con Bamboo Seguros.',
    'h1' => 'Seguro Catastrófico Colectivo para Empresas',
    'lead' => 'El seguro catastrófico colectivo protege a tus colaboradores frente a enfermedades graves y tratamientos de alto costo —como los oncológicos—, como beneficio grupal de la empresa. Te ayudamos a estructurar la cobertura adecuada.',
    'coberturas' => [
      'Gastos médicos mayores para colaboradores',
      'Enfermedades graves y de alto costo',
      'Beneficio grupal para la empresa',
      'Topes de cobertura elevados',
    ],
    'para_quien' => 'Empresas y pymes que protegen la salud de su equipo.',
    'faqs' => [],
  ],

];

/** Devuelve los seguros filtrados por segmento ('persona' | 'pyme'); 'ambos' aparece en los dos. */
function seguros_por_segmento(string $seg): array {
    global $SEGUROS;
    return array_filter($SEGUROS, fn($s) => $s['segmento'] === $seg || $s['segmento'] === 'ambos');
}
