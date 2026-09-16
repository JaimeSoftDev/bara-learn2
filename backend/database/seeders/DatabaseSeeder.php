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
            ['name' => 'Programación', 'icon' => 'code'],
            ['name' => 'Diseño', 'icon' => 'palette'],
            ['name' => 'Marketing', 'icon' => 'megaphone'],
            ['name' => 'Negocios', 'icon' => 'briefcase'],
            ['name' => 'Idiomas', 'icon' => 'languages'],
            ['name' => 'Fotografía y Vídeo', 'icon' => 'camera'],
            ['name' => 'Música', 'icon' => 'music'],
            ['name' => 'Desarrollo Personal', 'icon' => 'sparkles'],
        ])->map(fn ($c) => Category::create(['name' => $c['name'], 'slug' => Str::slug($c['name']), 'icon' => $c['icon']]));

        $admin = User::create([
            'name' => 'Administradora',
            'email' => 'admin@baralearn.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'headline' => 'Administración de la plataforma',
        ]);

        $teacher1 = User::create([
            'name' => 'Laura Gómez',
            'email' => 'laura@baralearn.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'headline' => 'Desarrolladora Full-Stack y formadora',
            'bio' => 'Más de 10 años enseñando programación web a miles de alumnos.',
        ]);

        $teacher2 = User::create([
            'name' => 'Marcos Ruiz',
            'email' => 'marcos@baralearn.test',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'headline' => 'Diseñador UX/UI y fotógrafo',
            'bio' => 'Ayudo a creativos a convertir su pasión en profesión.',
        ]);

        $students = collect(['Ana', 'Pedro', 'Sofía', 'Diego', 'Marta'])->map(
            fn ($name, $i) => User::create([
                'name' => $name.' Estudiante',
                'email' => Str::slug($name).'@baralearn.test',
                'password' => bcrypt('password'),
                'role' => 'student',
            ])
        );

        $coursesData = [
            [
                'teacher' => $teacher1,
                'category' => $categories[0],
                'title' => 'JavaScript Moderno desde Cero',
                'subtitle' => 'Domina ES6+, asincronía y el DOM construyendo proyectos reales',
                'level' => 'beginner',
                'price_cents' => 0,
                'sections' => [
                    'Introducción a JavaScript' => ['Variables y tipos de datos', 'Funciones y ámbito', 'Estructuras de control'],
                    'JavaScript Asíncrono' => ['Callbacks y Promesas', 'Async/Await', 'Fetch API'],
                ],
            ],
            [
                'teacher' => $teacher1,
                'category' => $categories[0],
                'title' => 'Vue 3 y Composition API en Profundidad',
                'subtitle' => 'Construye SPAs reactivas y mantenibles con Vue 3',
                'level' => 'intermediate',
                'price_cents' => 4999,
                'sections' => [
                    'Fundamentos de Vue 3' => ['Reactividad con ref y reactive', 'Componentes y props', 'Composables reutilizables'],
                    'Vue Router y Pinia' => ['Rutas anidadas y guardas', 'Gestión de estado con Pinia'],
                ],
            ],
            [
                'teacher' => $teacher2,
                'category' => $categories[1],
                'title' => 'Diseño UX/UI para No Diseñadores',
                'subtitle' => 'Principios de usabilidad y prototipado para crear productos increíbles',
                'level' => 'beginner',
                'price_cents' => 2999,
                'sections' => [
                    'Fundamentos del Diseño' => ['Teoría del color', 'Tipografía aplicada', 'Principios de composición'],
                    'Prototipado' => ['Wireframes efectivos', 'Prototipos interactivos'],
                ],
            ],
            [
                'teacher' => $teacher2,
                'category' => $categories[5],
                'title' => 'Fotografía Digital: Del Automático al Manual',
                'subtitle' => 'Aprende a controlar tu cámara y contar historias con imágenes',
                'level' => 'beginner',
                'price_cents' => 0,
                'sections' => [
                    'La Cámara y la Luz' => ['Triángulo de exposición', 'Composición fotográfica'],
                    'Edición Básica' => ['Flujo de trabajo en Lightroom'],
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
                'description' => '<p>'.$data['subtitle'].'</p><p>Este curso incluye vídeos, material escrito y ejercicios prácticos para que aprendas a tu ritmo.</p>',
                'thumbnail_url' => 'https://picsum.photos/seed/'.Str::slug($data['title']).'/640/360',
                'level' => $data['level'],
                'language' => 'es',
                'price_cents' => $data['price_cents'],
                'requirements' => ['Ganas de aprender', 'Un ordenador con conexión a internet'],
                'what_you_will_learn' => ['Fundamentos sólidos de la materia', 'Buenas prácticas profesionales', 'Un proyecto final para tu portfolio'],
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

        Certificate::create(['user_id' => $ana->id, 'course_id' => $freeCourse->id, 'code' => (string) Str::uuid(), 'issued_at' => now()->subDays(1)]);

        Review::create(['user_id' => $ana->id, 'course_id' => $freeCourse->id, 'rating' => 5, 'comment' => '¡Explicaciones clarísimas! Repetiré con más cursos de esta plataforma.']);

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
            'body' => 'Me ha encantado la lección, ¿hay algún reto adicional para practicar?',
        ]);
        $question->answers()->create([
            'user_id' => $freeCourse->teacher_id,
            'body' => '¡Genial que preguntes! Tienes ejercicios adicionales en el siguiente apartado.',
            'is_instructor_answer' => true,
        ]);

        $this->command?->info('Datos de demo creados. Usuarios de prueba (contraseña: password):');
        $this->command?->info('  Admin:    admin@baralearn.test');
        $this->command?->info('  Profesor: laura@baralearn.test / marcos@baralearn.test');
        $this->command?->info('  Alumnos:  ana@baralearn.test, pedro@baralearn.test, sofia@baralearn.test ...');
    }
}
