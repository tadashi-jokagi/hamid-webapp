<?php

namespace App\Http\Controllers\Student;

use App\File;
use App\Student;
use App\StudentFile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
        $student = new Student;

        return view('student.students.create', [
            'student' => $student,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name_first'=> 'required|min:1|max:20',
            'name_middle'=> 'nullable|min:1|max:20',
            'name_last'=> 'required|min:1|max:20',
            'contact_email1' => 'required|email|min:3|max:100',
            'contact_email2' => 'nullable|email|min:3|max:100',
            'contact_phone1' => 'nullable|min:9|max:20',
            'contact_phone2' => 'nullable|min:9|max:20',
            'code'=> 'required|min:5|max:15|unique:students,code',
            'programme_code' => 'required|min:1',
            'specialisation_code' => 'required|min:1',
            'enrollment_start_code' => 'required|string|min:1',
            'enrollment_finish_code' => 'required|string|min:1',
            'specialisation_codes' => 'array',
            'transportation_codes' => 'array',
            'area_codes' => 'array',
        ]);

        $student = new Student;
        \DB::transaction(function() use ($request, $student) {
            foreach ([
                'code',
                'name_first', 'name_middle', 'name_last',
                'contact_email1', 'contact_email2', 'contact_phone1', 'contact_phone2',
                'programme_code', 'specialisation_code', 'enrollment_start_code', 'enrollment_finish_code',
                'specialisation_codes', 'transportation_codes', 'area_codes',
            ] as $name) {
                switch ($name) {
                case 'specialisation_codes':
                case 'transportation_codes':
                case 'area_codes':
                    $potentials = $student->potentials;
                    $potentials[$name] = empty($request->$name) ? [] : $request->$name;
                    $student->potentials = $potentials;
                    break;

                default:
                    if (preg_match('/^prefer_transportation_/', $name)) {
                        $student->$name = empty($request->$name) ? false : true;
                    } else {
                        $student->$name = $request->$name === null ? '' : $request->$name;
                    }
                }
            }
            $student->potentials = $potentials;
            $student->save();

            if ($request->files->count()) {
                $uploadedFile = $request->file('file');
                if ($uploadedFile) {
                    if (\Config::get('school.attachment.allow.size') < $uploadedFile->getSize()) {
                        throw new \RuntimeException('Too large file.');
                    }

                    if (!in_array(\strtolower($uploadedFile->getClientOriginalExtension()), \Config::get('school.attachment.allow.extensions'))) {
                        throw new \RuntimeException('This file type does not allow uploading.');
                    }

                    $file = new File;
                    $file->name = $uploadedFile->getClientOriginalName();
                    $file->extension = $uploadedFile->getClientOriginalExtension();
                    $file->description = '';
                    $file->body = file_get_contents($uploadedFile->getPathname());
                    $file->size = $uploadedFile->getSize();
                }
                $file->save();

                $studentFile = new StudentFile;
                $studentFile->student_id = $student->id;
                $studentFile->file_id = $file->id;
                $studentFile->save();
            }

        });

        return redirect()->route('student.students.completed', [
        ]);
    }

    public function completed()
    {
        return view('student.students.completed');
    }
}
