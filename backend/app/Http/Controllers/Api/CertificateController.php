<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->certificates()
            ->with('course')
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'issued_at' => $c->issued_at,
                'course' => ['id' => $c->course->id, 'title' => $c->course->title, 'slug' => $c->course->slug],
            ]);
    }

    public function download(Request $request, Certificate $certificate)
    {
        abort_unless(
            $certificate->user_id === $request->user()->id || $request->user()->isAdmin(),
            403
        );

        $certificate->load(['user', 'course.teacher']);

        $pdf = Pdf::loadView('certificates.pdf', ['certificate' => $certificate])
            ->setPaper('a4', 'landscape');

        return $pdf->download("certificado-{$certificate->course->slug}.pdf");
    }

    /** Public endpoint to verify a certificate's authenticity by its code. */
    public function verify(string $code)
    {
        $certificate = Certificate::with(['user', 'course'])->where('code', $code)->first();

        if (! $certificate) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'student_name' => $certificate->user->name,
            'course_title' => $certificate->course->title,
            'issued_at' => $certificate->issued_at,
        ]);
    }
}
