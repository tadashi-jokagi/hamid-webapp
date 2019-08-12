<?php

namespace App\Http\Controllers\Admin;

use App\Student;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $searchConditions = $request->search_conditions ?? [];
        foreach (['keywords', 'programme_code', 'specialisation_code', 'enrollment_start_code', 'enrollment_finish_code', 'order_by'] as $name) {
            $searchConditions[$name] = $searchConditions[$name] ?? '';
        }

        $q = Student::query();

        if ($searchConditions['keywords']) {
            $k = sprintf('%%%s%%', $searchConditions['keywords']);
            $q->where('name_first', 'LIKE', $k);
            $q->orWhere('name_middle', 'LIKE', $k);
            $q->orWhere('name_last', 'LIKE', $k);
            $q->orWhere('contact_email1', 'LIKE', $k);
            $q->orWhere('contact_email2', 'LIKE', $k);
            $q->orWhere('contact_phone1', 'LIKE', $k);
            $q->orWhere('contact_phone2', 'LIKE', $k);
        }

        foreach (['enrollment_start_code', 'enrollment_finish_code', 'programme_code', 'specialisation_code'] as $name) {
            if ($searchConditions[$name]) {
                $q->where($name, $searchConditions[$name]);
            }
        }

        if ($searchConditions['order_by']) {
            $column = strtok($searchConditions['order_by'], ':');
            $ascDesc = strtok('');
            $q->orderBy($column, $ascDesc);
        }

        $students = $q->get();

        return view('admin.students.index', [
            'searchConditions' => $searchConditions,
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        //
        $student = new Student;

        return view('admin.students.create', [
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
        $student = new Student;

        $validatedData = $request->validate([
            'name_first'=> 'required|min:1|max:20',
            'name_middle'=> 'nullable|min:1|max:20',
            'name_last'=> 'required|min:1|max:20',
            'contact_email1' => 'required|email|min:3|max:100',
            'contact_email2' => 'nullable|email|min:3|max:100',
            'contact_phone1' => 'nullable|min:9|max:20',
            'contact_phone2' => 'nullable|min:9|max:20',
            'code'=> 'required|min:5|max:15|exists:students,code',
            'programme_code' => 'required|min:1',
            'specialisation_code' => 'required|min:1',
            'enrollment_start_code' => 'required|string|min:1',
            'enrollment_finish_code' => 'required|string|min:1',
            'specialisation_codes' => 'array',
            'transportation_codes' => 'array',
            'area_codes' => 'array',
        ]);

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

        return redirect()->route('admin.students.show', ['id' => $student->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Student  $students
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        return view('admin.students.show', ['student' => $student]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Student  $students
     * @return \Illuminate\Http\Response
     */
    public function edit(Student $student)
    {
        /*
        $potentials = $student->potentials;
        foreach (['area_codes', 'specialisation_codes', 'transportation_codes'] as $name) {
            $potentials[$name] = $potentials[$name]?? [];
        }
        $student->potentials = $potentials;
        */
        return view('admin.students.edit', ['student' => $student]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Student  $students
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Student $student)
    {
        $validatedData = $request->validate([
            'name_first'=> 'required|min:1|max:20',
            'name_middle'=> 'nullable|min:1|max:20',
            'name_last'=> 'required|min:1|max:20',
            'contact_email1' => 'required|email|min:3|max:100',
            'contact_email2' => 'nullable|email|min:3|max:100',
            'contact_phone1' => 'nullable|min:9|max:20',
            'contact_phone2' => 'nullable|min:9|max:20',
            'code'=> 'required|min:5|max:15|exists:students,code',
            'programme_code' => 'required|min:1',
            'specialisation_code' => 'required|min:1',
            'enrollment_start_code' => 'required|string|min:1',
            'enrollment_finish_code' => 'required|string|min:1',
            'specialisation_codes' => 'array',
            'transportation_codes' => 'array',
            'area_codes' => 'array',
        ]);

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
        $student->save();

        return redirect()->route('admin.students.show', ['id' => $student->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy(Student $student)
    {
        $student->delete();
        
        return redirect()->route('admin.students.index');
    }
}