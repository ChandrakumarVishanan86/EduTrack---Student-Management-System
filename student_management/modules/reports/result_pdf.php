<?php
/**
 * Student result sheet (PDF) using FPDF.
 * URL:  /report/{student_id}
 *   - student  : may only download their own sheet
 *   - lecturer / admin : any student
 */
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/auth.php';
require_once dirname(__DIR__, 2) . '/lib/fpdf/fpdf.php';

$user      = Auth::guard(['admin', 'lecturer', 'student']);
$studentId = (int) ($_GET['student_id'] ?? 0);

if ($user['role'] === 'student' && $studentId !== (int) $user['student_id']) {
    http_response_code(403);
    exit('You can only download your own result sheet.');
}

$student = StudentModel::find($studentId);
if (!$student) {
    http_response_code(404);
    exit('Student not found.');
}

$t   = MarkModel::transcript($studentId);
$att = AttendanceModel::percentage($studentId);

/** FPDF core fonts are Latin-1: convert UTF-8 text safely. */
function pdf_text(?string $s): string
{
    $s = (string) $s;
    $out = @iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $s);
    return $out === false ? preg_replace('/[^\x20-\x7E]/', '?', $s) : $out;
}

class ResultPDF extends FPDF
{
    public function Header(): void
    {
        // gradient-like banner (two rectangles)
        $this->SetFillColor(79, 70, 229);
        $this->Rect(0, 0, 210, 34, 'F');
        $this->SetFillColor(124, 58, 237);
        $this->Rect(120, 0, 90, 34, 'F');

        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 20);
        $this->SetXY(14, 9);
        $this->Cell(0, 8, pdf_text(APP_NAME));
        $this->SetFont('Helvetica', '', 10);
        $this->SetXY(14, 19);
        $this->Cell(0, 6, 'Official Student Result Sheet');
        $this->SetXY(-70, 12);
        $this->SetFont('Helvetica', '', 9);
        $this->Cell(56, 5, 'Issued: ' . date('d M Y'), 0, 2, 'R');
        $this->Cell(56, 5, 'Ref: SR-' . date('Ymd'), 0, 0, 'R');
        $this->Ln(30);
    }

    public function Footer(): void
    {
        $this->SetY(-14);
        $this->SetDrawColor(220, 220, 230);
        $this->Line(14, $this->GetY(), 196, $this->GetY());
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(0, 8, 'Computer generated document - valid without signature.   Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }

    /** label : value pair inside the info box */
    public function InfoRow(string $label, string $value, float $w = 90): void
    {
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(120, 120, 140);
        $this->Cell(30, 7, pdf_text($label));
        $this->SetFont('Helvetica', 'B', 10);
        $this->SetTextColor(30, 41, 59);
        $this->Cell($w - 30, 7, pdf_text($value));
    }
}

$pdf = new ResultPDF('P', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->SetMargins(14, 14, 14);
$pdf->SetAutoPageBreak(true, 20);
$pdf->AddPage();
$pdf->SetY(42);

// ------------------------------------------------ student info box
$pdf->SetFillColor(245, 246, 255);
$pdf->SetDrawColor(224, 226, 250);
$pdf->Rect(14, 42, 182, 38, 'DF');
$pdf->SetXY(18, 45);
$pdf->InfoRow('Name', $student['first_name'] . ' ' . $student['last_name']);
$pdf->InfoRow('Reg. No', $student['reg_no']);
$pdf->Ln(8);
$pdf->SetX(18);
$pdf->InfoRow('Department', $student['department']);
$pdf->InfoRow('Batch', (string) $student['batch_year']);
$pdf->Ln(8);
$pdf->SetX(18);
$pdf->InfoRow('Email', $student['email']);
$pdf->InfoRow('Status', $student['status']);
$pdf->Ln(8);
$pdf->SetX(18);
$pdf->InfoRow('Attendance', number_format($att, 1) . '%');
$pdf->InfoRow('Gender', $student['gender']);
$pdf->Ln(16);

// ------------------------------------------------ results table
$pdf->SetFont('Helvetica', 'B', 12);
$pdf->SetTextColor(30, 41, 59);
$pdf->Cell(0, 8, 'Academic Results', 0, 1);

$cols = [['Code', 24], ['Course', 70], ['Credits', 20], ['Marks', 22], ['Grade', 22], ['Grade Pt', 24]];
$pdf->SetFillColor(79, 70, 229);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Helvetica', 'B', 9);
$pdf->SetDrawColor(79, 70, 229);
foreach ($cols as $c) {
    $pdf->Cell($c[1], 9, $c[0], 1, 0, $c[0] === 'Course' || $c[0] === 'Code' ? 'L' : 'C', true);
}
$pdf->Ln();

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetDrawColor(226, 232, 240);
$fill = false;
if (!$t['rows']) {
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(182, 14, 'No results have been published yet.', 1, 1, 'C');
}
foreach ($t['rows'] as $r) {
    $pdf->SetFillColor(...($fill ? [248, 250, 252] : [255, 255, 255]));
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell($cols[0][1], 8, pdf_text($r['code']), 1, 0, 'L', true);
    $pdf->Cell($cols[1][1], 8, pdf_text($r['name']), 1, 0, 'L', true);
    $pdf->Cell($cols[2][1], 8, (string) $r['credits'], 1, 0, 'C', true);
    $pdf->Cell($cols[3][1], 8, number_format((float) $r['marks'], 1), 1, 0, 'C', true);

    // colour-coded grade
    $g = $r['grade'];
    if ($g === 'F') {
        $pdf->SetTextColor(220, 38, 38);
    } elseif (in_array($g, ['A+', 'A'], true)) {
        $pdf->SetTextColor(5, 150, 105);
    }
    $pdf->SetFont('Helvetica', 'B', 9);
    $pdf->Cell($cols[4][1], 8, $g, 1, 0, 'C', true);
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(30, 41, 59);
    $pdf->Cell($cols[5][1], 8, number_format((float) $r['grade_point'], 2), 1, 1, 'C', true);
    $fill = !$fill;
}

// ------------------------------------------------ summary
$pdf->Ln(8);
$boxes = [
    ['Average marks', number_format($t['average'], 2)],
    ['GPA (avg / 25)', number_format($t['gpa'], 2) . ' / 4.00'],
    ['Overall grade', $t['grade']],
    ['Courses graded', (string) count($t['rows'])],
];
$x = 14;
$y = $pdf->GetY();
if ($y > 240) {
    $pdf->AddPage();
    $y = $pdf->GetY() + 4;
}
foreach ($boxes as $b) {
    $pdf->SetFillColor(238, 240, 255);
    $pdf->SetDrawColor(199, 203, 250);
    $pdf->Rect($x, $y, 43, 22, 'DF');
    $pdf->SetXY($x, $y + 3);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $pdf->Cell(43, 5, $b[0], 0, 2, 'C');
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->SetTextColor(79, 70, 229);
    $pdf->Cell(43, 9, pdf_text($b[1]), 0, 0, 'C');
    $x += 46.33;
}

// ------------------------------------------------ signatures
$pdf->SetY($y + 40);
$pdf->SetDrawColor(148, 163, 184);
$pdf->SetTextColor(100, 116, 139);
$pdf->SetFont('Helvetica', '', 8);
$pdf->Line(14, $pdf->GetY(), 74, $pdf->GetY());
$pdf->Line(136, $pdf->GetY(), 196, $pdf->GetY());
$pdf->Ln(2);
$pdf->Cell(122, 5, 'Registrar');
$pdf->Cell(60, 5, 'Head of Department');

$pdf->SetTitle('Result Sheet - ' . pdf_text($student['reg_no']));
$pdf->SetAuthor(APP_NAME);

$file = 'Result_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $student['reg_no']) . '.pdf';
$pdf->Output('I', $file);       // 'D' to force download instead of inline view
