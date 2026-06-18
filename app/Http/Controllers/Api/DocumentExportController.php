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
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Word2007;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\Shared\Html;

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

    // Формат: Год.Месяц.День
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

class DocumentExportController extends Controller
{

    public function getCharacteristicOfStudent(Request $request)
    {
        $student = Student::findOrFail($request->student_id);
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);
        $organisation = PracticeBase::findOrFail($student->practice_base_id);

        $supervisorFull = explode("-", $student->practice_supervisor);
        $supervisorNameParts = explode(' ', $supervisorFull[0]);

        $supervisorfirstName = $supervisorNameParts[0];
        $supervisormiddleName = $supervisorNameParts[1];
        $supervisorlastName = $supervisorNameParts[2];

        $supervisorShortName = $supervisorlastName . ' ' . mb_substr($supervisorfirstName, 0, 1) . '. ' . mb_substr($supervisormiddleName, 0, 1) . '.';

        $practiceType = "";

        if ($practice->type === "up") {
            $practiceType = "учебную практику";
        } else if ($practice->type === "pp") {
            $practiceType = "производственную практику";
        } else if ($practice->type === "pdp") {
            $practiceType = "производственную практику (преддипломную)";
        }

        $templatePath = storage_path('app/templates/Kharakteristika_professionalnoy_deyatelnosti.docx');
        $templateProcessor = new TemplateProcessor($templatePath);

        $period = new TextRun();
        Html::addHtml($period, formatDateRange($practice->start_date, $practice->end_date));

        $templateProcessor->setValue('student_name', $student->full_name);
        $templateProcessor->setValue('group_number', $group->name);
        $templateProcessor->setValue('specialty', $specialty->code . " " . $specialty->specialty);
        $templateProcessor->setValue('qualification', $specialty->qualification);
        $templateProcessor->setValue('type', $practiceType);
        // $templateProcessor->setValue('period', formatDateRange($practice->start_date, $practice->end_date));
        $templateProcessor->setComplexValue('period', $period);
        $templateProcessor->setValue('organisation', $organisation->organisation);
        $templateProcessor->setValue('position', $supervisorFull[1]);
        $templateProcessor->setValue('supervisor', $supervisorShortName);

        $fileName = 'Kharakteristika_' . $student->full_name . '.docx';
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

        // Формируем данные таблицы
        $data1 = [
            ['№ п/п', 'Учебная группа, курс', 'Фамилия, Имя, Отчество студента', "Наименование образовательной программы", "Сроки практической подготовки", "Компоненты образовательной программы"],
        ];
        $data2 = [
            ['№ п/п', 'Адрес помещения', 'Наименование помещения'],
        ];

        $rowNumber1 = 1;
        $rowNumber2 = 2;
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
                $base->address,
                $base->organisation,
            ];
            $rowNumber2++;
        }

        // if (count($students) === 0) {
        //     $data1[] = ['', '', 'Нет студентов', '', '', ''];
        // }

        // Отрисовываем таблицу
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

    public function getCertificatSheetDocument(Request $request)
    {
        $student = Student::findOrFail($request->student_id);
        $group = StudentGroup::findOrFail($request->group_id);
        $specialty = Specialty::findOrFail($group->specialty_id);
        $practice = Practice::findOrFail($request->practice_id);
        $organisation = PracticeBase::findOrFail($student->practice_base_id);

        $supervisorFull = explode("-", $student->practice_supervisor);
        $supervisorNameParts = explode(' ', $supervisorFull[0]);

        $supervisorfirstName = $supervisorNameParts[0];
        $supervisormiddleName = $supervisorNameParts[1];
        $supervisorlastName = $supervisorNameParts[2];

        $supervisorShortName = $supervisorlastName . ' ' . mb_substr($supervisorfirstName, 0, 1) . '. ' . mb_substr($supervisormiddleName, 0, 1) . '.';

        $templatePath = "";

        if ($practice->type === "up") {
            $templatePath = storage_path('app/templates/Attestatsionny_list_uchebnaya_praktika.docx');
        } else if ($practice->type === "pp") {
            $templatePath = storage_path('app/templates/Attestatsionny_list_proizvodstvennaya_praktika.docx');
        } else if ($practice->type === "pdp") {
            $templatePath = storage_path('app/templates/Attestatsionny_list_proizvodstvennaya_preddiplomnaya_praktika.docx');
        }

        $templateProcessor = new TemplateProcessor($templatePath);

        $period = new TextRun();
        Html::addHtml($period, formatDateRange($practice->start_date, $practice->end_date));

        $templateProcessor->setValue('student_name', $student->full_name);
        $templateProcessor->setValue('group_number', $group->name);
        $templateProcessor->setValue('specialty', $specialty->code . " " . $specialty->specialty);
        $templateProcessor->setValue('qualification', $specialty->qualification);
        $templateProcessor->setValue('practice', $practice->name);
        $templateProcessor->setComplexValue('period', $period);
        $templateProcessor->setValue('organisation', $organisation->organisation);
        $templateProcessor->setValue('position', $supervisorFull[1]);
        $templateProcessor->setValue('supervisor', $supervisorShortName);

        $fileName = 'Attestatsionny_list_' . $student->full_name . '.docx';
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

        $supervisorFull = explode("-", $student->practice_supervisor);
        $supervisorNameParts = explode(' ', $supervisorFull[0]);
        $supervisorfirstName = $supervisorNameParts[0];
        $supervisormiddleName = $supervisorNameParts[1];
        $supervisorlastName = $supervisorNameParts[2];
        $supervisorShortName = $supervisorlastName . ' ' . mb_substr($supervisorfirstName, 0, 1) . '. ' . mb_substr($supervisormiddleName, 0, 1) . '.';

        $practiceType = "";

        if ($practice->type === "up") {
            $practiceType = "учебной практики";
        } else if ($practice->type === "pp") {
            $practiceType = "производственной практики";
        } else if ($practice->type === "pdp") {
            $practiceType = "производственной практики (преддипломной)";
        }

        $templatePath = storage_path('app/templates/otziv.docx');
        $templateProcessor = new TemplateProcessor($templatePath);
        $specialtyValue = new TextRun();
        Html::addHtml($specialtyValue, '<span style="font-family: Times New Roman, Times, serif; font-size: 16px;"><u> ' . $specialty->code . " " . $specialty->specialty . "</u></span>");

        $templateProcessor->setValue('type', $practiceType);
        $templateProcessor->setComplexValue('specialty', $specialtyValue);
        $templateProcessor->setValue('qualification', $specialty->qualification);
        $templateProcessor->setValue('practice', $practice->name);
        $templateProcessor->setValue('organisation', $organisation->organisation);
        $templateProcessor->setValue('position', $supervisorFull[1]);
        $templateProcessor->setValue('supervisor', $supervisorShortName);

        $fileName = 'Otziv_' . $group->name . "_" . $organisation->organisation . '.docx';
        $tempFile = storage_path('app/temp/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    public function getDirectionDocument(Request $request)
    {

        $group = StudentGroup::findOrFail($request->group_id);
        $practice = Practice::findOrFail($request->practice_id);

        $practiceType = "";

        if ($practice->type === "up") {
            $practiceType = "учебной практики";
        } else if ($practice->type === "pp") {
            $practiceType = "производственной практики";
        } else if ($practice->type === "pdp") {
            $practiceType = "производственной практики (преддипломной)";
        }

        function formatDate($start_date_str, $end_date_str)
        {
            $start = DateTime::createFromFormat('Y-m-d', $start_date_str);
            $end = DateTime::createFromFormat('Y-m-d', $end_date_str);

            return $start->format('d.m.Y') . " по " . $end->format('d.m.Y');;
        }

        function getPracticeBaseName($id)
        {
            if (!$id) {
                return 'Не указана';
            }

            $practiceBase = PracticeBase::find($id); // find вместо findOrFail

            return $practiceBase ? $practiceBase->organisation : 'База практики не найдена';
        }
        $result = $group->students->map(function ($student) use ($group, $practiceType, $practice) {
            return [
                'course' => $group->course,
                'student_name' => $student->full_name,
                'period' => formatDate($practice->start_date, $practice->end_date),
                'type' => $practiceType,
                'organisation' => getPracticeBaseName($student->practice_base_id)
            ];
        })->toArray();

        $templatePath = storage_path('app/templates/napravlenie.docx');
        ini_set('pcre.backtrack_limit', '10000000');
        ini_set('pcre.jit', '1');
        $templateProcessor = new TemplateProcessor($templatePath);

        $templateProcessor->cloneBlock('block_student', count($result), true, false, $result);
        // $templateProcessor->setValue('course', json_encode($result));

        $fileName = 'Napravlenie_' . $group->name . '.docx';
        $tempFile = storage_path('app/public/' . $fileName);

        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }
}

