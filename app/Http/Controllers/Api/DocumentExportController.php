<?php

namespace App\Http\Controllers\Api;

use App\Models\Practice;
use App\Models\PracticeBase;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\StudentGroup;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Html;

function declineFioGenitive(string $fullName): string
{
    $parts = preg_split('/\s+/u', trim($fullName));

    if (count($parts) < 3) {
        return $fullName; // not a full ФИО, return as-is
    }

    [$surname, $firstName, $patronymic] = $parts;

    $gender = guessGender($patronymic);
    $declinedSurname = declineSurname($surname, $gender);
    $initials = mb_substr($firstName, 0, 1) . '.' . mb_substr($patronymic, 0, 1) . '.';

    return $declinedSurname . ' ' . $initials;
}

function guessGender(string $patronymic): string
{
    if (mb_substr($patronymic, -3) === 'вна') {
        return 'female';
    }
    if (mb_substr($patronymic, -3) === 'вич') {
        return 'male';
    }
    return 'female'; // fallback
}

function declineSurname(string $surname, string $gender): string
{
    $replacements = $gender === 'female'
        ? [
            'ова' => 'овой',
            'ева' => 'евой',
            'ина' => 'иной',
            'ына' => 'ыной',
            'ая' => 'ой',
            'яя' => 'ей',
        ]
        : [
            'ов' => 'ова',
            'ев' => 'ева',
            'ин' => 'ина',
            'ын' => 'ына',
            'ский' => 'ского',
            'цкий' => 'цкого',
            'ой' => 'ого',
        ];

    // sort by suffix length, longest first, so e.g. "ский" matches before "ий"
    uksort($replacements, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));

    foreach ($replacements as $ending => $newEnding) {
        $len = mb_strlen($ending);
        if (mb_substr($surname, -$len) === $ending) {
            return mb_substr($surname, 0, -$len) . $newEnding;
        }
    }

    return $surname; // indeclinable (e.g. -ко, -ян, foreign names) → unchanged
}

function formatDateRange($start_date_str, $end_date_str)
{
    $months = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря'
    ];

    $start = DateTime::createFromFormat('Y-m-d', $start_date_str);
    $end = DateTime::createFromFormat('Y-m-d', $end_date_str);

    $start_day = $start->format('j');
    $start_month = $months[(int) $start->format('n')];
    $start_year = $start->format('Y');

    $end_day = $end->format('j');
    $end_month = $months[(int) $end->format('n')];
    $end_year = $end->format('Y');

    return "с «<u> $start_day </u>" . "»" . "<u>   $start_month   </u>$start_year года     по «<u> $end_day </u>" . "»" . "<u>   $end_month   </u>$end_year года";
}

function buildPeriodTextRun(string $html): TextRun
{
    $textRun = new TextRun();
    Html::addHtml($textRun, $html);
    return $textRun;
}

class DocumentExportController extends Controller
{

    private function getPracticeTypeLabel(string $type, string $case = 'genitive'): string
    {
        $labels = [
            'genitive' => [
                'up' => 'учебной практики',
                'pp' => 'производственной практики',
                'pdp' => 'производственной практики (преддипломной)',
            ],
            'accusative' => [
                'up' => 'учебную практику',
                'pp' => 'производственную практику',
                'pdp' => 'производственную практику (преддипломную)',
            ],
        ];

        return $labels[$case][$type] ?? '';
    }

    private function getAttestationTemplatePath(string $practiceType): ?string
    {
        return match ($practiceType) {
            'up' => storage_path('app/templates/Attestatsionny_list_uchebnaya_praktika.docx'),
            'pp' => storage_path('app/templates/Attestatsionny_list_proizvodstvennaya_praktika.docx'),
            'pdp' => storage_path('app/templates/Attestatsionny_list_proizvodstvennaya_preddiplomnaya_praktika.docx'),
            default => null,
        };
    }

    private function parseSupervisor(?string $raw): array
    {
        if (!$raw || !str_contains($raw, '-')) {
            throw new \RuntimeException('Не указан руководитель практики или некорректный формат (ожидается "ФИО-Должность")');
        }

        $supervisorFull = explode('-', $raw, 2); // limit 2, чтобы дефис в должности не ломал разбор
        $supervisorNameParts = preg_split('/\s+/', trim($supervisorFull[0]));

        if (count($supervisorNameParts) < 3) {
            throw new \RuntimeException('ФИО руководителя должно содержать фамилию, имя и отчество');
        }

        [$lastName, $firstName, $middleName] = $supervisorNameParts;

        $shortName = $lastName . ' ' . mb_substr($firstName, 0, 1) . '. ' . mb_substr($middleName, 0, 1) . '.';

        return [
            'short_name' => $shortName,
            'position' => trim($supervisorFull[1] ?? ''),
        ];
    }

    private function formatSimpleDateRange(string $start_date_str, string $end_date_str): string
    {
        $start = DateTime::createFromFormat('Y-m-d', $start_date_str);
        $end = DateTime::createFromFormat('Y-m-d', $end_date_str);

        return $start->format('d.m.Y') . ' по ' . $end->format('d.m.Y');
    }

    private function getPracticeBaseName(?int $id): string
    {
        if (!$id) {
            return 'Не указана';
        }

        $practiceBase = PracticeBase::find($id);

        return $practiceBase ? $practiceBase->organisation : 'База практики не найдена';
    }

    public function getCharacteristicOfStudent(Request $request)
    {
        $student = Student::findOrFail($request->student_id);
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);
        $organisation = PracticeBase::findOrFail($student->practice_base_id);

        try {
            $supervisorData = $this->parseSupervisor($student->practice_supervisor);
            $innerSupervisorData = $this->parseSupervisor($student->inner_supervisor);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $practiceType = $this->getPracticeTypeLabel($practice->type, 'accusative');

        $templatePath = storage_path('app/templates/Kharakteristika_professionalnoy_deyatelnosti.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', 1, true, false);

        $period = new TextRun();
        Html::addHtml($period, formatDateRange($practice->start_date, $practice->end_date));

        $templateProcessor->setValue('student_name', $student->full_name);
        $templateProcessor->setValue('group_number', $group->name);
        $templateProcessor->setValue('specialty', $specialty->code . " " . $specialty->specialty);
        $templateProcessor->setValue('qualification', $specialty->qualification);
        $templateProcessor->setValue('type', $practiceType);
        $templateProcessor->setComplexValue('period', $period);
        $templateProcessor->setValue('organisation', $organisation->organisation);
        $templateProcessor->setValue('position', $supervisorData['position']);
        $templateProcessor->setValue('supervisor', $supervisorData['short_name']);
        $templateProcessor->setValue('inner_position', $innerSupervisorData['position']);
        $templateProcessor->setValue('inner_supervisor', $innerSupervisorData['short_name']);

        $fileName = 'Kharakteristika_' . $student->full_name . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function getCharacteristicOfGroupDocument(Request $request)
    {
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);

        $practiceType = $this->getPracticeTypeLabel($practice->type, 'accusative');

        $period = (new DateTime($practice->start_date))->format('d.m.Y')
            . ' по ' .
            (new DateTime($practice->end_date))->format('d.m.Y');

        $errors = [];

        $result = $group->students->map(function (Student $student) use ($group, $specialty, $practiceType, $period, &$errors) {
            $organisation = PracticeBase::find($student->practice_base_id);

            try {
                $supervisorData = $this->parseSupervisor($student->practice_supervisor);
                $innerSupervisorData = $this->parseSupervisor($student->inner_supervisor);
            } catch (\RuntimeException $e) {
                $errors[] = $student->full_name . ': ' . $e->getMessage();
                return null;
            }

            return [
                'student_name' => $student->full_name,
                'group_number' => $group->name,
                'specialty' => $specialty->code . " " . $specialty->specialty,
                'qualification' => $specialty->qualification,
                'type' => $practiceType,
                'period' => $period,
                'organisation' => $organisation ? $organisation->organisation : 'База практики не указана',
                'position' => $supervisorData['position'],
                'supervisor' => $supervisorData['short_name'],
                'inner_position' => $innerSupervisorData['position'],
                'inner_supervisor' => $innerSupervisorData['short_name'],
            ];
        })->filter()->values()->toArray();

        if (!empty($errors)) {
            return response()->json([
                'message' => 'Не удалось обработать данные части студентов',
                'errors' => $errors,
            ], 422);
        }

        $templatePath = storage_path('app/templates/Kharakteristika_professionalnoy_deyatelnosti.docx');
        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.jit', '1');

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', count($result), true, false, $result);

        $fileName = 'Kharakteristika_' . $group->name . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function getAgreementDocument(Request $request)
    {
        $templatePath = storage_path('app/templates/Dogovor_o_prakticheskoy_podgotovke_s_prilozheniem.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        $group = StudentGroup::findOrFail($request->group_id);
        $students = $group->students;
        $practice = Practice::findOrFail($request->selectedPracticeId);

        $bases = PracticeBase::findOrFail(json_decode($request->basesIds, true));

        $name = $practice->name;
        $start_date = $practice->start_date;
        $end_date = $practice->end_date;
        $course = $group->course;
        $group_name = $group->name;

        $practicePeriod = date('d.m.Y', strtotime($start_date)) . ' - ' . date('d.m.Y', strtotime($end_date));

        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000'];
        $table1 = new Table($tableStyle);
        $table2 = new Table($tableStyle);

        $fontStyle = ['name' => 'Times New Roman', 'size' => 9, 'color' => '000000', 'bold' => false];
        $paragraphStyle = ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER];

        $data1 = [
            ['№ п/п', 'Учебная группа, курс', 'Фамилия, Имя, Отчество студента', "Наименование образовательной программы", "Сроки практической подготовки", "Компоненты образовательной программы"],
        ];
        $data2 = [
            ['№ п/п', 'Адрес помещения', 'Наименование помещения'],
        ];

        $rowNumber1 = 1;
        $rowNumber2 = 1;
        foreach ($students as $index => $student) {
            if ($index === 0) {
                $data1[] = [
                    $rowNumber1,
                    "{$group_name}, {$course} курс",
                    $student->full_name,
                    $name,
                    $practicePeriod,
                    "",
                ];
            } else {
                $data1[] = [
                    $rowNumber1,
                    "",
                    $student->full_name,
                    "",
                    "",
                    "",
                ];
            }
            $rowNumber1++;
        }
        foreach ($bases as $base) {
            $data2[] = [
                $rowNumber2,
                "",
                $base->organisation,
            ];
            $rowNumber2++;
        }

        foreach ($data1 as $rowIndex => $rowData) {
            $table1->addRow();

            foreach ($rowData as $cellIndex => $cellText) {
                $cellStyle = [
                    'cellMarginTop' => 100,
                    'cellMarginBottom' => 100,
                    'cellMarginLeft' => 100,
                    'cellMarginRight' => 100,
                ];

                $cellWidth = 2500;

                switch ($cellIndex) {
                    case 0:
                        $cellWidth = 500;
                        $cellStyle['cellMarginLeft'] = 30;
                        $cellStyle['cellMarginRight'] = 30;
                        break;
                    case 1:
                        $cellWidth = 1200;
                        break;
                    case 2:
                        $cellWidth = 3500;
                        $cellStyle['cellMarginLeft'] = 120;
                        break;
                    case 3:
                        $cellWidth = 3000;
                        break;
                    case 4:
                        $cellWidth = 1800;
                        break;
                    case 5:
                        $cellWidth = 2000;
                        break;
                }

                $table1->addCell($cellWidth, $cellStyle)->addText($cellText, $fontStyle, $paragraphStyle);
            }
        }
        foreach ($data2 as $rowIndex => $rowData) {
            $table2->addRow();

            foreach ($rowData as $cellIndex => $cellText) {
                $cellStyle = [
                    'cellMarginTop' => 100,
                    'cellMarginBottom' => 100,
                    'cellMarginLeft' => 100,
                    'cellMarginRight' => 100,
                ];

                $cellWidth = 2500;

                switch ($cellIndex) {
                    case 0:
                        $cellWidth = 500;
                        $cellStyle['cellMarginLeft'] = 30;
                        $cellStyle['cellMarginRight'] = 30;
                        break;
                    case 1:
                        $cellWidth = 5700;
                        break;
                    case 2:
                        $cellWidth = 5700;
                        break;
                }

                $table2->addCell($cellWidth, $cellStyle)->addText($cellText, $fontStyle, $paragraphStyle);
            }
        }

        $templateProcessor->setComplexBlock('practice_table', $table1);
        $templateProcessor->setComplexBlock('bases_table', $table2);

        $savePath = storage_path('app/public/generated_document.docx');
        $templateProcessor->saveAs($savePath);

        return response()->download($savePath)->deleteFileAfterSend(true);
    }

    private function generateAttestationSheetFile(
        Student $student,
        StudentGroup $group,
        Specialty $specialty,
        Practice $practice,
        string $templatePath,
        string $outputDir
    ): string {
        if (!$student->practice_base_id) {
            throw new \RuntimeException('Не указана база практики');
        }

        $organisation = PracticeBase::find($student->practice_base_id);

        if (!$organisation) {
            throw new \RuntimeException('База практики не найдена');
        }

        $supervisorData = $this->parseSupervisor($student->practice_supervisor);
        $innerSupervisorData = $this->parseSupervisor($student->inner_supervisor);

        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', 1, true, false);

        $period = new TextRun();
        Html::addHtml($period, formatDateRange($practice->start_date, $practice->end_date));

        $templateProcessor->setValue('student_name', $student->full_name);
        $templateProcessor->setValue('group_number', $group->name);
        $templateProcessor->setValue('specialty', $specialty->code . ' ' . $specialty->specialty);
        $templateProcessor->setValue('qualification', $specialty->qualification);
        $templateProcessor->setValue('practice', $practice->name);
        $templateProcessor->setComplexValue('period', $period);
        $templateProcessor->setValue('organisation', $organisation->organisation);
        $templateProcessor->setValue('position', $supervisorData['position']);
        $templateProcessor->setValue('supervisor', $supervisorData['short_name']);
        $templateProcessor->setValue('inner_position', $innerSupervisorData['position']);
        $templateProcessor->setValue('inner_supervisor', $innerSupervisorData['short_name']);

        $fileName = 'Attestatsionny_list_' . $student->full_name . '.docx';
        $filePath = rtrim($outputDir, '/') . '/' . $fileName;

        $templateProcessor->saveAs($filePath);

        return $filePath;
    }

    public function getCertificatSheetDocument(Request $request)
    {
        $student = Student::findOrFail($request->student_id);
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);

        $templatePath = $this->getAttestationTemplatePath($practice->type);

        if (!$templatePath) {
            return response()->json(['message' => 'Неизвестный тип практики'], 422);
        }

        try {
            $filePath = $this->generateAttestationSheetFile(
                $student,
                $group,
                $specialty,
                $practice,
                $templatePath,
                storage_path('app/public')
            );
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function getCertificatSheetGroupDocument(Request $request)
    {
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);

        $templatePath = $this->getAttestationTemplatePath($practice->type);

        if (!$templatePath || !file_exists($templatePath)) {
            abort(422, 'Не найден шаблон для указанного типа практики');
        }

        $periodHtml = formatDateRange($practice->start_date, $practice->end_date);

        $errors = [];

        $result = $group->students->map(function (Student $student) use ($group, $specialty, $practice, &$errors) {
            try {
                $supervisor = $this->parseSupervisor($student->practice_supervisor);
                $innerSupervisor = $this->parseSupervisor($student->inner_supervisor);
            } catch (\RuntimeException $e) {
                $errors[] = $student->full_name . ': ' . $e->getMessage();
                return null;
            }

            $organisation = PracticeBase::find($student->practice_base_id);

            return [
                'student_name' => $student->full_name,
                'group_number' => $group->name,
                'specialty' => $specialty->code . ' ' . $specialty->specialty,
                'qualification' => $specialty->qualification,
                'practice' => $practice->name,
                'organisation' => $organisation ? $organisation->organisation : 'База практики не указана',
                'position' => $supervisor['position'],
                'supervisor' => $supervisor['short_name'],
                'inner_position' => $innerSupervisor['position'],
                'inner_supervisor' => $innerSupervisor['short_name'],
            ];
        })->filter()->values()->toArray();

        if (!empty($errors)) {
            return response()->json(['message' => 'Ошибка в данных руководителей практики', 'errors' => $errors], 422);
        }

        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.jit', '1');

        $templateProcessor = new TemplateProcessor($templatePath);

        // Клонируем блок с индексацией (#1, #2...), без авто-замены текста
        $templateProcessor->cloneBlock('block_student', count($result), true, true);

        foreach ($result as $i => $row) {
            $idx = $i + 1;

            foreach ($row as $key => $value) {
                $templateProcessor->setValue("{$key}#{$idx}", $value);
            }

            // period подставляем отдельно как форматированный TextRun
            $templateProcessor->setComplexValue("period#{$idx}", buildPeriodTextRun($periodHtml));
        }

        $fileName = 'Attestatsionny_list_' . $group->name . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function getReviewDocument(Request $request)
    {
        $student = Student::findOrFail($request->student_id);
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);
        $organisation = PracticeBase::findOrFail($student->practice_base_id);

        try {
            $supervisorData = $this->parseSupervisor($student->practice_supervisor);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $practiceType = $this->getPracticeTypeLabel($practice->type, 'genitive');

        $templatePath = storage_path('app/templates/otziv.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->setValue('type', $practiceType);
        $templateProcessor->setValue('specialty', $specialty->code . " " . $specialty->specialty);
        $templateProcessor->setValue('qualification', $specialty->qualification);
        $templateProcessor->setValue('practice', $practice->name);
        $templateProcessor->setValue('organisation', $organisation->organisation);
        $templateProcessor->setValue('position', $supervisorData['position']);
        $templateProcessor->setValue('supervisor', $supervisorData['short_name']);

        $fileName = 'Otziv_' . $group->name . "_" . $organisation->organisation . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    // public function getReviewGroupDocument(Request $request)
    // {
    //     $group = StudentGroup::findOrFail($request->group_id);
    //     $specialty = Specialty::findOrFail($group->specialty_id);
    //     $practice = Practice::findOrFail($request->practice_id);

    //     $practiceType = $this->getPracticeTypeLabel($practice->type, 'genitive');

    //     $result = [];
    //     $errors = [];

    //     foreach ($group->students as $student) {
    //         try {
    //             $supervisorData = $this->parseSupervisor($student->practice_supervisor);
    //         } catch (\RuntimeException $e) {
    //             $errors[] = $student->full_name . ': ' . $e->getMessage();
    //             continue;
    //         }

    //         $organisation = PracticeBase::findOrFail($student->practice_base_id);

    //         $result[] = [
    //             'student_name' => $student->full_name,
    //             'type' => $practiceType,
    //             'specialty' => $specialty->code . ' ' . $specialty->specialty,
    //             'qualification' => $specialty->qualification,
    //             'practice' => $practice->name,
    //             'organisation' => $organisation->organisation,
    //             'position' => $supervisorData['position'],
    //             'supervisor' => $supervisorData['short_name'],
    //         ];
    //     }

    //     if (!empty($errors)) {
    //         return response()->json(['message' => 'Ошибка в данных руководителей практики', 'errors' => $errors], 422);
    //     }

    //     $templatePath = storage_path('app/templates/otziv.docx');
    //     ini_set('pcre.backtrack_limit', '10000000');
    //     ini_set('pcre.jit', '1');
    //     $templateProcessor = new TemplateProcessor($templatePath);

    //     $templateProcessor->cloneBlock('block_student', count($result), true, false, $result);

    //     $fileName = 'Otziv_' . $group->name . '.docx';
    //     $tempFile = storage_path('app/public/' . $fileName);

    //     $templateProcessor->saveAs($tempFile);

    //     return response()->download($tempFile)->deleteFileAfterSend(true);
    // }

    public function getOrderingDocument(Request $request)
    {
        $group = StudentGroup::findOrFail($request->group_id);
        $practice = Practice::findOrFail($request->practice_id);

        $practiceGenitiveType = $this->getPracticeTypeLabel($practice->type, 'genitive');
        $practiceAccusativeType = $this->getPracticeTypeLabel($practice->type, 'accusative');

        $templatePath = storage_path('app/templates/prikaz.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        $period = "с " . (new DateTime($practice->start_date))->format('d.m.Y')
            . ' по ' .
            (new DateTime($practice->end_date))->format('d.m.Y');

        $practiceBases = PracticeBase::whereIn(
            'id',
            $group->students->pluck('practice_base_id')->unique()
        )->get()->keyBy('id');

        $basesInfo = $group->students
            ->groupBy('practice_base_id')
            ->map(function ($students, $practiceBaseId) use ($practiceBases) {
                return [
                    'organisation' => $practiceBases[$practiceBaseId]->organisation,
                    'students' => $students->map(fn($s) => [
                        'full_name' => $s->full_name,
                    ])->values(),
                ];
            })
            ->values();

        $templateProcessor->setValue('start_date', $practice->start_date);
        $templateProcessor->setValue('group_number', $group->name);
        $templateProcessor->setValue('type_accusative', $practiceAccusativeType);
        $templateProcessor->setValue('practice', $practice->name);
        $templateProcessor->setValue('period', $period);
        $templateProcessor->setValue('teacher', declineFioGenitive($group->teacher_name));
        $templateProcessor->setValue('type_genitive', $practiceGenitiveType);
        // $templateProcessor->setValue('inner_supervisor', $practice->name);

        $templateProcessor->cloneBlock('organisation_block', count($basesInfo), true, true);

        foreach ($basesInfo as $i => $group) {
            $orgIndex = $i + 1;

            $templateProcessor->setValue("organisation#{$orgIndex}", $group['organisation']);

            // Nested block: clone the student list inside this specific organisation instance
            $templateProcessor->cloneBlock("student_block#{$orgIndex}", count($group['students']), true, true);

            foreach ($group['students'] as $j => $student) {
                $studentIndex = $j + 1;

                $templateProcessor->setValue(
                    "student_number#{$orgIndex}#{$studentIndex}",
                    $studentIndex
                );
                $templateProcessor->setValue(
                    "student_name#{$orgIndex}#{$studentIndex}",
                    $student['full_name']
                );
            }
        }

        $fileName = 'Prikaz_' . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function getDirectionGroupDocument(Request $request)
    {
        $group = StudentGroup::findOrFail($request->group_id);
        $practice = Practice::findOrFail($request->practice_id);

        $practiceType = $this->getPracticeTypeLabel($practice->type, 'genitive');

        $result = $group->students->map(function ($student) use ($group, $practiceType, $practice) {
            return [
                'course' => $group->course,
                'student_name' => $student->full_name,
                'period' => $this->formatSimpleDateRange($practice->start_date, $practice->end_date),
                'type' => $practiceType,
                'organisation' => $this->getPracticeBaseName($student->practice_base_id)
            ];
        })->toArray();

        $templatePath = storage_path('app/templates/napravlenie.docx');
        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.jit', '1');
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', count($result), true, false, $result);

        $fileName = 'Napravlenie_' . $group->name . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function getDirectionDocument(Request $request)
    {
        $group = StudentGroup::findOrFail($request->group_id);
        $practice = Practice::findOrFail($request->practice_id);
        $student = Student::findOrFail($request->student_id);

        $practiceType = $this->getPracticeTypeLabel($practice->type, 'genitive');

        $templatePath = storage_path('app/templates/napravlenie.docx');
        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.jit', '1');
        $templateProcessor = new TemplateProcessor($templatePath);
        $templateProcessor->cloneBlock('block_student', 1, true, false);

        $templateProcessor->setValue('course', $group->course);
        $templateProcessor->setValue('student_name', $student->full_name);
        $templateProcessor->setValue('period', $this->formatSimpleDateRange($practice->start_date, $practice->end_date));
        $templateProcessor->setValue('type', $practiceType);
        $templateProcessor->setValue('organisation', $this->getPracticeBaseName($student->practice_base_id));

        // 'course' => $group->course,
        // 'student_name' => $student->full_name,
        // 'period' => $this->formatSimpleDateRange($practice->start_date, $practice->end_date),
        // 'type' => $practiceType,
        // 'organisation' => $this->getPracticeBaseName($student->practice_base_id)

        $fileName = 'Napravlenie_' . $group->name . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}