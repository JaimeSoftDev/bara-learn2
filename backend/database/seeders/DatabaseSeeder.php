<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Review;
use App\Models\Section;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /** Placeholder public YouTube video used for every demo lesson. */
    private const DEMO_YOUTUBE_URL = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    public function run(): void
    {
        $categories = collect([
            ['name' => 'Fundamentos', 'icon' => 'dna'],
            ['name' => 'Molecular', 'icon' => 'microscope'],
            ['name' => 'Evolución', 'icon' => 'leaf'],
            ['name' => 'Aplicada', 'icon' => 'flask'],
            ['name' => 'Clínica', 'icon' => 'stethoscope'],
            ['name' => 'Bioinformática', 'icon' => 'code'],
        ])->map(fn ($c) => Category::create(['name' => $c['name'], 'slug' => Str::slug($c['name']), 'icon' => $c['icon']]));

        $admin = User::create([
            'name' => 'Administradora',
            'email' => 'admin@adntrate.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'headline' => 'Administración de la plataforma',
        ]);

        $teacher1 = User::create([
            'name' => 'Dra. Elena Castro',
            'email' => 'elena@adntrate.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'headline' => 'Catedrática de Genética Molecular',
            'bio' => 'Más de 15 años investigando y enseñando genética molecular en la universidad. Autora de dos manuales de biología del gen.',
        ]);

        $teacher2 = User::create([
            'name' => 'Dr. Javier Ortiz',
            'email' => 'javier@adntrate.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'headline' => 'Profesor de Genética Evolutiva y Poblacional',
            'bio' => 'Investigador en genética de poblaciones. Ayuda a estudiantes de grado a conectar la teoría con problemas reales de laboratorio.',
        ]);

        $students = collect(['Ana', 'Pedro', 'Sofía', 'Diego', 'Marta'])->map(
            fn ($name, $i) => User::create([
                'name' => $name.' Estudiante',
                'email' => Str::slug($name).'@adntrate.test',
                'password' => bcrypt('password'),
                'role' => 'student',
            ])
        );

        $coursesData = [
            [
                'teacher' => $teacher2,
                'category' => $categories[0], // Fundamentos
                'title' => 'Genética Mendeliana',
                'subtitle' => 'Leyes de la herencia, cruces y probabilidad genética desde cero',
                'level' => 'beginner',
                'price_cents' => 0,
                'sections' => [
                    'Las Leyes de Mendel' => ['Segregación y dominancia', 'Cruces monohíbridos', 'Cruces dihíbridos'],
                    'Probabilidad Genética' => ['Cuadros de Punnett', 'Herencia ligada al sexo'],
                ],
            ],
            [
                'teacher' => $teacher1,
                'category' => $categories[1], // Molecular
                'title' => 'Biología Molecular del Gen',
                'subtitle' => 'Estructura del ADN, replicación, transcripción y traducción',
                'level' => 'intermediate',
                'price_cents' => 4999,
                'sections' => [
                    'Estructura y Replicación del ADN' => ['La doble hélice', 'Enzimas de la replicación', 'Reparación del ADN'],
                    'Del Gen a la Proteína' => ['Transcripción', 'Traducción y el código genético'],
                ],
            ],
            [
                'teacher' => $teacher2,
                'category' => $categories[2], // Evolución
                'title' => 'Genética de Poblaciones',
                'subtitle' => 'Hardy-Weinberg, deriva, selección y flujo génico aplicados',
                'level' => 'intermediate',
                'price_cents' => 3999,
                'sections' => [
                    'El Equilibrio Hardy-Weinberg' => ['Frecuencias alélicas y genotípicas', 'Condiciones del equilibrio'],
                    'Fuerzas Evolutivas' => ['Deriva genética', 'Selección natural', 'Flujo génico y mutación'],
                ],
            ],
            [
                'teacher' => $teacher1,
                'category' => $categories[3], // Aplicada
                'title' => 'Edición Génica con CRISPR',
                'subtitle' => 'Fundamentos y aplicaciones de la edición del genoma',
                'level' => 'advanced',
                'price_cents' => 5999,
                'sections' => [
                    'Fundamentos de CRISPR-Cas9' => ['Origen bacteriano del sistema', 'Diseño de guías ARN'],
                    'Aplicaciones' => ['Edición en modelos animales', 'Terapia génica y bioética'],
                ],
            ],
        ];

        $publishedCourses = collect();

        foreach ($coursesData as $data) {
            /** @var Course $course */
            $course = Course::create([
                'teacher_id' => $data['teacher']->id,
                'category_id' => $data['category']->id,
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'subtitle' => $data['subtitle'],
                'description' => '<p>'.$data['subtitle'].'</p><p>Este curso incluye vídeos, material escrito y ejercicios prácticos para que aprendas a tu ritmo, con el mismo rigor que en un laboratorio universitario.</p>',
                'thumbnail_url' => null,
                'level' => $data['level'],
                'language' => 'es',
                'price_cents' => $data['price_cents'],
                'requirements' => ['Conocimientos básicos de biología de bachillerato', 'Ganas de aprender'],
                'what_you_will_learn' => ['Fundamentos sólidos de la materia', 'Resolución de problemas tipo examen', 'Vocabulario y notación propios de la genética'],
                'status' => 'published',
                'published_at' => now()->subDays(random_int(5, 90)),
            ]);

            $sectionPosition = 0;
            $lessonGlobalIndex = 0;

            foreach ($data['sections'] as $sectionTitle => $lessonTitles) {
                /** @var Section $section */
                $section = $course->sections()->create([
                    'title' => $sectionTitle,
                    'position' => $sectionPosition++,
                ]);

                $lessonPosition = 0;

                foreach ($lessonTitles as $lessonTitle) {
                    $section->lessons()->create([
                        'title' => $lessonTitle,
                        'youtube_url' => self::DEMO_YOUTUBE_URL,
                        'youtube_video_id' => Lesson::extractYoutubeId(self::DEMO_YOUTUBE_URL),
                        'content' => "<p>En esta lección aprenderás sobre <strong>{$lessonTitle}</strong>.</p><p>Toma nota de los conceptos clave y practica con los ejercicios propuestos.</p>",
                        'position' => $lessonPosition++,
                        'duration_seconds' => random_int(240, 900),
                        // First lesson of the first section is a free preview.
                        'is_preview' => $lessonGlobalIndex === 0,
                    ]);
                    $lessonGlobalIndex++;
                }
            }

            $publishedCourses->push($course);
        }

        // --- Enrollments ---
        $freeCourse = $publishedCourses->firstWhere('price_cents', 0);
        $paidCourse = $publishedCourses->firstWhere('price_cents', '>', 0);

        // Ana enrolls for free and completes the whole course (-> certificate).
        $ana = $students[0];
        Enrollment::create([
            'user_id' => $ana->id,
            'course_id' => $freeCourse->id,
            'source' => 'free',
            'price_paid_cents' => 0,
            'enrolled_at' => now()->subDays(20),
        ]);

        foreach ($freeCourse->lessons as $lesson) {
            LessonProgress::create(['user_id' => $ana->id, 'lesson_id' => $lesson->id, 'completed_at' => now()->subDays(random_int(1, 15))]);
        }

        // --- Quizzes / exams ---
        $mendelSection = $freeCourse->sections->firstWhere('title', 'Las Leyes de Mendel');
        $sectionQuiz = $this->createQuiz($freeCourse, $mendelSection, 'Examen: Las Leyes de Mendel', [
            ['¿Qué establece la primera ley de Mendel (ley de la segregación)?', [
                ['Los alelos de un gen se separan durante la formación de los gametos.', true],
                ['Los genes de distintos cromosomas se heredan siempre juntos.', false],
            ]],
            ['En un cruce monohíbrido Aa x Aa, con A dominante, ¿qué proporción fenotípica se espera en la F2?', [
                ['3:1', true],
                ['1:1', false],
            ]],
        ]);

        $finalExam = $this->createQuiz($freeCourse, null, 'Examen final: Genética Mendeliana', [
            ['¿Para qué sirve un cuadro de Punnett?', [
                ['Para predecir la combinación de alelos en la descendencia.', true],
                ['Para extraer ADN en el laboratorio.', false],
            ]],
            ['En humanos, la herencia ligada al sexo afecta principalmente a genes situados en:', [
                ['El cromosoma Y', false],
                ['El cromosoma X', true],
            ]],
        ]);

        // Ana passes both exams, matching her earned certificate below.
        $this->seedPassingAttempt($sectionQuiz, $ana);
        $this->seedPassingAttempt($finalExam, $ana);

        // The paid course also has a section quiz, left unattempted so
        // Pedro/Sofía can try it out from a fresh enrollment.
        $dnaSection = $paidCourse->sections->firstWhere('title', 'Estructura y Replicación del ADN');
        $this->createQuiz($paidCourse, $dnaSection, 'Examen: Estructura y Replicación del ADN', [
            ['¿Qué forma tridimensional describe la molécula de ADN?', [
                ['Doble hélice', true],
                ['Lámina plegada', false],
            ]],
            ['¿Qué enzima sintetiza la nueva cadena de ADN durante la replicación?', [
                ['ADN polimerasa', true],
                ['ARN polimerasa', false],
            ]],
        ]);

        Certificate::create(['user_id' => $ana->id, 'course_id' => $freeCourse->id, 'code' => (string) Str::uuid(), 'issued_at' => now()->subDays(1)]);

        Review::create(['user_id' => $ana->id, 'course_id' => $freeCourse->id, 'rating' => 5, 'comment' => 'Por fin entendí la genética mendeliana. Explicaciones clarísimas y ejercicios muy parecidos a los de mi facultad.']);

        // Pedro buys the paid course through Stripe (simulated as already paid).
        $pedro = $students[1];
        $order = Order::create([
            'user_id' => $pedro->id,
            'course_id' => $paidCourse->id,
            'amount_cents' => $paidCourse->price_cents,
            'currency' => 'eur',
            'payment_method' => 'stripe',
            'status' => 'paid',
            'stripe_checkout_session_id' => 'cs_test_demo_'.Str::random(10),
            'paid_at' => now()->subDays(10),
        ]);
        Enrollment::create([
            'user_id' => $pedro->id,
            'course_id' => $paidCourse->id,
            'source' => 'stripe',
            'price_paid_cents' => $order->amount_cents,
            'enrolled_at' => now()->subDays(10),
        ]);

        // Sofía pays the teacher in cash in person; the teacher grants her
        // access manually and it looks exactly like a paid enrollment.
        $sofia = $students[2];
        $cashOrder = Order::create([
            'user_id' => $sofia->id,
            'course_id' => $paidCourse->id,
            'amount_cents' => $paidCourse->price_cents,
            'currency' => 'eur',
            'payment_method' => 'cash',
            'status' => 'paid',
            'created_by' => $paidCourse->teacher_id,
            'notes' => 'Pagado en efectivo tras la clase presencial del 3 de septiembre.',
            'paid_at' => now()->subDays(6),
        ]);
        Enrollment::create([
            'user_id' => $sofia->id,
            'course_id' => $paidCourse->id,
            'source' => 'cash',
            'granted_by' => $paidCourse->teacher_id,
            'price_paid_cents' => $cashOrder->amount_cents,
            'enrolled_at' => now()->subDays(6),
        ]);

        // A question on the first lesson of the free course, answered by the instructor.
        $firstLesson = $freeCourse->sections->first()->lessons->first();
        $question = Question::create([
            'user_id' => $ana->id,
            'lesson_id' => $firstLesson->id,
            'title' => '¿Dónde puedo practicar lo aprendido?',
            'body' => 'Me ha encantado la lección, ¿hay algún reto adicional para practicar los cruces?',
        ]);
        $question->answers()->create([
            'user_id' => $freeCourse->teacher_id,
            'body' => '¡Genial que preguntes! Tienes ejercicios adicionales con solución en el siguiente apartado.',
            'is_instructor_answer' => true,
        ]);

        $this->command?->info('Datos de demo creados. Usuarios de prueba (contraseña: password):');
        $this->command?->info('  Admin:    admin@adntrate.test');
        $this->command?->info('  Profesores: elena@adntrate.test / javier@adntrate.test');
        $this->command?->info('  Alumnos:  ana@adntrate.test, pedro@adntrate.test, sofia@adntrate.test ...');
    }

    /**
     * @param  array<int, array{0: string, 1: array<int, array{0: string, 1: bool}>}>  $questions
     */
    private function createQuiz(Course $course, ?Section $section, string $title, array $questions, int $passingScore = 70): Quiz
    {
        $quiz = Quiz::create([
            'course_id' => $course->id,
            'section_id' => $section?->id,
            'title' => $title,
            'passing_score' => $passingScore,
        ]);

        foreach ($questions as $position => [$questionText, $options]) {
            $question = $quiz->questions()->create(['question' => $questionText, 'position' => $position]);

            foreach ($options as $optionPosition => [$optionText, $isCorrect]) {
                $question->options()->create([
                    'option_text' => $optionText,
                    'is_correct' => $isCorrect,
                    'position' => $optionPosition,
                ]);
            }
        }

        return $quiz;
    }

    private function seedPassingAttempt(Quiz $quiz, User $user): void
    {
        $quiz->loadMissing('questions.options');

        $attempt = $quiz->attempts()->create([
            'user_id' => $user->id,
            'score' => 100,
            'passed' => true,
            'submitted_at' => now()->subDays(2),
        ]);

        foreach ($quiz->questions as $question) {
            $attempt->answers()->create([
                'quiz_question_id' => $question->id,
                'quiz_option_id' => $question->correctOption()?->id,
                'is_correct' => true,
            ]);
        }
    }
}
