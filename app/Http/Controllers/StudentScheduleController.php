<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ScheduleCourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StudentScheduleController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $response = Http::post('https://quiztoxml.ucas.edu.ps/api/login', [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        $data = $response->json();

        if ($response->failed() || !isset($data['Token'])) {
            return response()->json([
                'message' => 'كلمة المرور او اسم المستخدم خطا'
            ], 401);
        }

        $student = Student::updateOrCreate(
            ['student_id' => $data['data']['user_id']],
            [
                'name'  => $data['data']['user_ar_name'] ?? $data['data']['user_en_name'] ?? 'غير معروف',
                'token' => $data['Token'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح',
            'student' => $student,
        ]);
    }

    public function getSchedule(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginResponse = Http::post('https://quiztoxml.ucas.edu.ps/api/login', [
            'username' => $request->username,
            'password' => $request->password,
        ]);

        $loginData = $loginResponse->json();

        if ($loginResponse->failed() || !isset($loginData['Token'])) {
            return response()->json([
                'message' => 'كلمة المرور او اسم المستخدم خطا'
            ], 401);
        }

        $token  = $loginData['Token'];
        $userId = $loginData['data']['user_id'];
        $name   = $loginData['data']['user_ar_name'] ?? $loginData['data']['user_en_name'] ?? 'غير معروف';

        $student = Student::updateOrCreate(
            ['student_id' => $userId],
            ['name' => $name, 'token' => $token]
        );

        $tableResponse = Http::post('https://quiztoxml.ucas.edu.ps/api/get-table', [
            'user_id' => $userId,
            'token'   => $token,
        ]);

        $tableData = $tableResponse->json();

        if ($tableResponse->failed()) {
            return response()->json([
                'message'  => 'فشل في جلب الجدول الدراسي',
                'raw_data' => $tableData,
            ], 500);
        }

        ScheduleCourse::where('student_id', $student->id)->delete();

        $savedCourses = [];

        $courses = $tableData['data'] ?? $tableData['schedule'] ?? $tableData ?? [];

        if (is_array($courses)) {
            foreach ($courses as $course) {
                if (!is_array($course)) continue;

                $saved = ScheduleCourse::create([
                    'student_id'    => $student->id,
                    'course_code'   => $course['course_code']   ?? $course['code']     ?? null,
                    'course_name'   => $course['course_name']   ?? $course['name']     ?? $course['subject'] ?? 'غير محدد',
                    'instructor'    => $course['instructor']    ?? $course['teacher']  ?? $course['doctor']  ?? null,
                    'room'          => $course['room']          ?? $course['location'] ?? $course['hall']    ?? null,
                    'day'           => $course['day']           ?? $course['day_name'] ?? 'غير محدد',
                    'start_time'    => $course['start_time']    ?? $course['from']     ?? '00:00:00',
                    'end_time'      => $course['end_time']      ?? $course['to']       ?? '00:00:00',
                    'section'       => $course['section']       ?? $course['group']    ?? null,
                    'academic_year' => $course['academic_year'] ?? $course['year']     ?? '2025/2026',
                    'semester'      => $course['semester']      ?? $course['term']     ?? 'الأول',
                ]);

                $savedCourses[] = $saved;
            }
        }

        return response()->json([
            'success'       => true,
            'message'       => 'تم جلب الجدول الدراسي وحفظه بنجاح',
            'student'       => $student,
            'courses_count' => count($savedCourses),
            'courses'       => $savedCourses,
            'raw_data'      => $tableData,
        ]);
    }

    public function show($studentId)
    {
        $student = Student::where('student_id', $studentId)
            ->with('courses')
            ->firstOrFail();

        return response()->json([
            'student'  => $student,
            'schedule' => $student->courses,
        ]);
    }
}
