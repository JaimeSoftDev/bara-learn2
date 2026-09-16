<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Helvetica', sans-serif;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }
        .frame {
            border: 14px solid #4f46e5;
            padding: 60px;
            height: 100%;
            box-sizing: border-box;
            text-align: center;
        }
        .brand {
            font-size: 14px;
            letter-spacing: 4px;
            color: #4f46e5;
            text-transform: uppercase;
            margin-bottom: 40px;
        }
        h1 {
            font-size: 34px;
            margin: 0 0 10px;
            color: #111827;
        }
        .subtitle {
            font-size: 16px;
            color: #6b7280;
            margin-bottom: 40px;
        }
        .student-name {
            font-size: 30px;
            font-weight: bold;
            margin: 20px 0;
            color: #4f46e5;
            border-bottom: 2px solid #e5e7eb;
            display: inline-block;
            padding-bottom: 10px;
        }
        .course-title {
            font-size: 22px;
            margin: 20px 0 40px;
        }
        .meta {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 60px;
        }
        .signature {
            margin-top: 50px;
            font-size: 14px;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="frame">
        <div class="brand">Bara Learn</div>
        <h1>Certificado de Finalización</h1>
        <div class="subtitle">Se otorga el presente certificado a</div>
        <div class="student-name">{{ $certificate->user->name }}</div>
        <div class="subtitle">por haber completado satisfactoriamente el curso</div>
        <div class="course-title">&ldquo;{{ $certificate->course->title }}&rdquo;</div>
        <div class="signature">{{ $certificate->course->teacher->name }}<br>Instructor/a del curso</div>
        <div class="meta">
            Emitido el {{ $certificate->issued_at->format('d/m/Y') }} &middot;
            Código de verificación: {{ $certificate->code }}
        </div>
    </div>
</body>
</html>
