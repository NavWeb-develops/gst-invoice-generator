<?php
/**
 * GST Invoice Generator - Sobha Malineni
 * Table-based layout for pixel-perfect print & PDF
 */

$supplier = [
    'name'    => 'SOBHA MALINENI',
    'address' => 'SOMA HEIGHTS, #-3, SIDDHIVINAYAK SOCIETY, KARVEROAD, PUNE - 411 038',
    'tel'     => '020-25466466',
    'mobile'  => '9890355555',
    'pan'     => 'AEHPM7166E',
    'gstin'   => '27-AEHPM7166E-1-Z-B',
    'bank_name'      => 'Saraswat Bank Limited',
    'bank_branch'    => 'Erandwane, Karve Road Branch',
    'account_number' => '036500100204780',
    'ifsc_code'      => 'SRCB0000036',
];

$invoice = null;
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice = [
        'recipient_pan'   => strtoupper(trim($_POST['recipient_pan'] ?? '')),
        'recipient_gstin' => strtoupper(trim($_POST['recipient_gstin'] ?? '')),
        'company_name'    => strtoupper(trim($_POST['company_name'] ?? '')),
        'company_address' => trim($_POST['company_address'] ?? ''),
        'company_cin'     => strtoupper(trim($_POST['company_cin'] ?? '')),
        'invoice_no'      => trim($_POST['invoice_no'] ?? ''),
        'invoice_date'    => trim($_POST['invoice_date'] ?? ''),
        'due_date'        => trim($_POST['due_date'] ?? ''),
        'period_from'     => trim($_POST['period_from'] ?? ''),
        'period_to'       => trim($_POST['period_to'] ?? ''),
        'particulars'     => trim($_POST['particulars'] ?? ''),
        'sac_code'        => trim($_POST['sac_code'] ?? ''),
        'amount'          => floatval($_POST['amount'] ?? 0),
        'reverse_charge'  => trim($_POST['reverse_charge'] ?? 'No'),
        'cgst_rate'       => floatval($_POST['cgst_rate'] ?? 9),
        'sgst_rate'       => floatval($_POST['sgst_rate'] ?? 9),
        'igst_rate'       => floatval($_POST['igst_rate'] ?? 0),
        'apply_igst'      => isset($_POST['apply_igst']) ? true : false,
    ];

    if (empty($invoice['recipient_pan']))   $errors[] = 'Recipient PAN is required';
    if (empty($invoice['recipient_gstin'])) $errors[] = 'Recipient GSTIN is required';
    if (empty($invoice['company_name']))    $errors[] = 'Company Name is required';
    if (empty($invoice['invoice_no']))      $errors[] = 'Invoice Number is required';
    if (empty($invoice['invoice_date']))    $errors[] = 'Invoice Date is required';
    if (empty($invoice['due_date']))        $errors[] = 'Due Date is required';
    if (empty($invoice['particulars']))     $errors[] = 'Particulars are required';
    if ($invoice['amount'] <= 0)            $errors[] = 'Amount must be greater than 0';

    if (empty($errors)) {
        $taxable = $invoice['amount'];
        if ($invoice['apply_igst']) {
            $cgst = 0; $sgst = 0;
            $igst = round($taxable * $invoice['igst_rate'] / 100);
        } else {
            $cgst = round($taxable * $invoice['cgst_rate'] / 100);
            $sgst = round($taxable * $invoice['sgst_rate'] / 100);
            $igst = 0;
        }
        $total_tax   = $cgst + $sgst + $igst;
        $grand_total = $taxable + $total_tax;

        $invoice['cgst']        = $cgst;
        $invoice['sgst']        = $sgst;
        $invoice['igst']        = $igst;
        $invoice['total_tax']   = $total_tax;
        $invoice['grand_total'] = $grand_total;
        $invoice['amount_words'] = convertToWords($grand_total);

        $invoice['date_display']        = date('d/m/Y', strtotime($invoice['invoice_date']));
        $invoice['due_date_display']    = date('d/m/Y', strtotime($invoice['due_date']));
        $invoice['period_from_display'] = !empty($invoice['period_from']) ? date('d-m-Y', strtotime($invoice['period_from'])) : '';
        $invoice['period_to_display']   = !empty($invoice['period_to']) ? date('d-m-Y', strtotime($invoice['period_to'])) : '';
        $invoice['period_display']      = '';
        if ($invoice['period_from_display'] && $invoice['period_to_display']) {
            $invoice['period_display'] = $invoice['period_from_display'] . ' to ' . $invoice['period_to_display'];
        }
    }
}

function convertToWords($number) {
    if ($number == 0) return 'Zero Only';
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $number = (int)$number;
    $words  = '';
    if ($number >= 10000000) { $words .= convertToWords(floor($number / 10000000)) . ' Crore '; $number %= 10000000; }
    if ($number >= 100000)   { $words .= convertToWords(floor($number / 100000)) . ' Lakh '; $number %= 100000; }
    if ($number >= 1000)     { $words .= convertToWords(floor($number / 1000)) . ' Thousand '; $number %= 1000; }
    if ($number >= 100)      { $words .= $ones[floor($number / 100)] . ' Hundred '; $number %= 100; }
    if ($number >= 20)       { $words .= $tens[floor($number / 10)] . ' '; $number %= 10; }
    if ($number > 0)         { $words .= $ones[$number] . ' '; }
    return 'Rupees ' . trim($words) . ' Only';
}

function formatIndian($num) {
    $num = number_format($num, 0, '.', '');
    $len = strlen($num);
    if ($len <= 3) return $num;
    $last3 = substr($num, -3);
    $rest  = substr($num, 0, $len - 3);
    $result = '';
    while (strlen($rest) > 2) {
        $result = ',' . substr($rest, -2) . $result;
        $rest   = substr($rest, 0, strlen($rest) - 2);
    }
    return $rest . $result . ',' . $last3;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GST Invoice Generator</title>
    <style>
        :root {
            --bg: #eef1f5;
            --card: #ffffff;
            --header-bg: #1b2a3d;
            --accent: #2b5278;
            --accent-hover: #1d3d5c;
            --accent-light: #e9f0fa;
            --text: #1a2332;
            --text-sec: #4a5568;
            --text-muted: #718096;
            --border: #cbd5e0;
            --border-lt: #e8ecf1;
            --err: #c53030;
            --err-bg: #fff5f5;
            --ok: #276749;
            --radius: 10px;
            --radius-sm: 6px;
            --shadow: 0 4px 16px rgba(0,0,0,0.08);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }

        /* ── Page Header ── */
        .page-hdr { background: var(--header-bg); color: #fff; padding: 22px 0; text-align: center; }
        .page-hdr h1 { font-size: 21px; font-weight: 700; letter-spacing: .5px; }
        .page-hdr p { font-size: 13px; color: #94a3b8; margin-top: 3px; }

        .wrap { max-width: 900px; margin: 0 auto; padding: 24px 16px; }

        /* ── Form ── */
        .fcard { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; margin-bottom: 20px; }
        .ftitle { background: var(--accent); color: #fff; padding: 11px 20px; font-size: 13px; font-weight: 600; letter-spacing: .3px; text-transform: uppercase; }
        .fgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 18px; }
        .fgrp { display: flex; flex-direction: column; gap: 4px; }
        .fgrp.fw { grid-column: 1/-1; }
        .fgrp label { font-size: 11px; font-weight: 600; color: var(--text-sec); text-transform: uppercase; letter-spacing: .4px; }
        .fgrp label .rq { color: var(--err); }
        .fgrp input, .fgrp textarea, .fgrp select { padding: 9px 12px; border: 1.5px solid var(--border); border-radius: var(--radius-sm); font-size: 14px; color: var(--text); background: #fff; transition: border-color .2s, box-shadow .2s; font-family: inherit; }
        .fgrp input:focus, .fgrp textarea:focus, .fgrp select:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(43,82,120,.12); }
        .fgrp textarea { resize: vertical; min-height: 66px; }
        .fgrp input::placeholder { color: var(--text-muted); font-size: 13px; }

        .chkrow { display: flex; align-items: center; gap: 8px; padding: 10px 18px; background: var(--accent-light); border-top: 1px solid var(--border-lt); }
        .chkrow input[type="checkbox"] { width: 17px; height: 17px; accent-color: var(--accent); }
        .chkrow label { font-size: 13px; font-weight: 500; color: var(--text-sec); cursor: pointer; }

        .btnrow { padding: 14px 18px; display: flex; gap: 12px; flex-wrap: wrap; border-top: 1px solid var(--border-lt); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 11px 26px; font-size: 14px; font-weight: 600; border: none; border-radius: var(--radius-sm); cursor: pointer; transition: all .2s; font-family: inherit; letter-spacing: .3px; text-decoration: none; }
        .btn-p { background: var(--accent); color: #fff; }
        .btn-p:hover { background: var(--accent-hover); transform: translateY(-1px); }
        .btn-s { background: #e8edf4; color: var(--text); border: 1.5px solid var(--border); }
        .btn-s:hover { background: #d2d8e0; }
        .btn-g { background: var(--ok); color: #fff; }
        .btn-g:hover { background: #1e5438; transform: translateY(-1px); }

        .errs { background: var(--err-bg); border: 1px solid #fed7d7; border-radius: var(--radius-sm); padding: 12px 18px; margin-bottom: 18px; }
        .errs p { color: var(--err); font-size: 13px; font-weight: 500; padding: 2px 0; }

        /* ══════════════════════════════════════════════════════════════
           INVOICE — Pure HTML Table for rock-solid print/PDF layout
           ══════════════════════════════════════════════════════════════ */
        .inv-page {
            width: 210mm;
            min-height: auto;
            max-width: 100%;
            margin: 0 auto 24px;
            background: #fff;
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            font-size: 13px;
            line-height: 1.35;
        }
        .inv-page table {
            width: 100%;
            border-collapse: collapse;
            border: 2.5px solid #000; /* OUTER BORDER */
        }

        .inv-page td, .inv-page th {
            border: 1px solid #000; /* INNER CLEAN */
            padding: 5px 8px;
            vertical-align: top;
        }

        /* SECTION DIVIDERS */
        .inv-page .thick {
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
        }

        /* VERTICAL DIVIDER */
        .inv-page .v-thick {
            border-left: 2px solid #000 !important;
        }

        /* Title bar */
        .inv-page .title-bar {
            background: #d4d4d4;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            padding: 7px 0;
        }

        /* Supplier header */
        .inv-page .sup-name {
            font-size: 20px;
            font-weight: 700;
            text-decoration: underline;
        }
        .inv-page .sup-block {
            text-align: center;
            padding: 8px 10px;
            font-size: 12px;
            line-height: 1.5;
        }

        /* Detail rows */
        .inv-page .lbl { font-weight: 700; }
        .inv-page .val { text-align: right; }
        .inv-page .val-c { text-align: center; }
        .inv-page .r-align { text-align: right; }
        .inv-page .b { font-weight: 700; }
        .inv-page .i { font-style: italic; }
        .inv-page .bi { font-weight: 700; font-style: italic; }
        .inv-page .no-border-b { border-bottom: none; }
        .inv-page .no-border-t { border-top: none; }
        .inv-page .thin-b { border-bottom: 1px solid #888; }
        .inv-page .bg-grey { background: #d4d4d4; }

        /* Footer bank */
        .inv-page .bank-cell {
            font-size: 12px;
            line-height: 1.55;
            vertical-align: top;
            padding: 8px 10px;
        }
        .inv-page .sig-cell {
            text-align: center;
            vertical-align: top;
            padding: 8px 10px;
        }

        /* Action buttons */
        .inv-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 20px;
            max-width: 210mm;
            margin-left: auto;
            margin-right: auto;
        }

        /* ── Print ── */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm 8mm;
            }
            html, body {
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print { display: none !important; }
            .wrap { padding: 0 !important; max-width: 100% !important; }
            .inv-page {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                page-break-inside: avoid;
                page-break-after: avoid;
            }
            .inv-page table { page-break-inside: avoid; }
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .fgrid { grid-template-columns: 1fr; }
            .btnrow, .inv-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
            .inv-page { width: 100%; font-size: 11px; }
            .inv-page .sup-name { font-size: 16px; }
            .inv-page .title-bar { font-size: 13px; }
            .inv-page td, .inv-page th { padding: 3px 5px; font-size: 11px; }
            .inv-page .sup-block { font-size: 10px; }
            .inv-page .bank-cell { font-size: 10px; }
        }
        @media (max-width: 480px) {
            .wrap { padding: 12px 6px; }
            .inv-page td, .inv-page th { padding: 2px 3px; font-size: 10px; }
            .inv-page .sup-name { font-size: 14px; }
            .fgrid { padding: 12px; gap: 10px; }
            .fgrp input, .fgrp textarea, .fgrp select { padding: 8px 10px; font-size: 13px; }
        }
    </style>
</head>
<body>

<header class="page-hdr no-print">
    <h1>GST Invoice Generator</h1>
    <p>Sobha Malineni — Fill details below to generate invoice</p>
</header>

<div class="wrap">

<?php if (!empty($errors)): ?>
<div class="errs no-print">
    <?php foreach ($errors as $e): ?>
        <p>⚠ <?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($invoice && empty($errors)): ?>
<!-- ════════════════════════ INVOICE OUTPUT ════════════════════════ -->

<div class="inv-actions no-print">
    <button class="btn btn-g" onclick="window.print();">🖨️ Print Invoice</button>
    <a href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" class="btn btn-s">← Generate Another</a>
</div>

<div class="inv-page" id="invoiceArea">
<table>
    <!-- ROW 1: GST INVOICE title -->
    <tr>
    <td colspan="4" class="title-bar bg-grey thick">GST INVOICE</td>
</tr>

<tr>
    <td colspan="4" class="sup-block thick">
        <div class="sup-name"><?= $supplier['name'] ?></div>
        Address : <?= $supplier['address'] ?><br>
        Tel No. <?= $supplier['tel'] ?> Mobile : <?= $supplier['mobile'] ?>
    </td>
</tr>

    <!-- ROW 3: RECEIPIENT DETAILS label + SUPPLIER PAN -->
    <tr>
    <td colspan="2" class="bi thin-b">RECEIPIENT DETAILS :</td>
    <td class="lbl thin-b v-thick">SUPPLIER PAN</td>
    <td class="val thin-b"><?= $supplier['pan'] ?></td>
</tr>

<tr>
    <td colspan="2" class="thin-b">
    <strong>PAN:-</strong> <?= htmlspecialchars($invoice['recipient_pan']) ?>&nbsp;&nbsp;&nbsp;
    <strong>GSTIN:-</strong> <?= htmlspecialchars($invoice['recipient_gstin']) ?>
</td>
    <td class="lbl thin-b v-thick">SUPPLIER GSTIN</td>
    <td class="val thin-b"><?= $supplier['gstin'] ?></td>
</tr>

    <!-- ROW 5: Company Name + Invoice No -->
    <tr>
    <td colspan="2" rowspan="3" class="thin-b">
        <span class="bi"><?= htmlspecialchars($invoice['company_name']) ?></span><br>
        <em><?= nl2br(htmlspecialchars($invoice['company_address'])) ?></em>
    </td>
    <td class="lbl thin-b v-thick">Invoice No.</td>
    <td class="val thin-b"><?= htmlspecialchars($invoice['invoice_no']) ?></td>
</tr>

<tr>
    <td class="lbl thin-b v-thick">Date</td>
    <td class="val thin-b"><?= htmlspecialchars($invoice['date_display']) ?></td>
</tr>

<tr>
    <td class="lbl thin-b v-thick">Due Date</td>
    <td class="val thin-b"><?= htmlspecialchars($invoice['due_date_display']) ?></td>
</tr>

    <!-- ROW 8: CIN + Invoice Period -->
    <tr>
    <td colspan="2" class="thin-b">
        <?php if(!empty($invoice['company_cin'])): ?>
            <strong>CIN - <?= htmlspecialchars($invoice['company_cin']) ?></strong>
        <?php endif; ?>
    </td>
    <td class="lbl thin-b v-thick">Invoice Period</td>
    <td class="val thin-b"><?= htmlspecialchars($invoice['period_display']) ?></td>
</tr>

    <!-- ROW 9: Items Header -->
<tr class="thick">
    <td colspan="2" class="lbl" style="text-align:center;">Particulars</td>
    <td class="lbl" style="text-align:center;">SAC CODE</td>
    <td class="lbl" style="text-align:center;">Amount (Rs.)</td>
</tr>

    <!-- ROW 10: Item -->
    <tr>
        <td colspan="2" style="min-height:50px; vertical-align:top;">
            <?= nl2br(htmlspecialchars($invoice['particulars'])) ?>
        </td>
        <td class="val-c" style="vertical-align:middle;"><?= htmlspecialchars($invoice['sac_code']) ?></td>
        <td class="r-align" style="vertical-align:top;">
            <?= formatIndian($invoice['amount']) ?><br>
            <span style="color:#999;">-</span>
        </td>
    </tr>

    <!-- TOTAL -->
    <tr class="thick">
    <td colspan="3" class="r-align b">TOTAL</td>
    <td class="r-align b"><?= formatIndian($invoice['amount']) ?></td>
</tr>

<tr>
    <td colspan="3" class="r-align b">Tax Payable on Reverse charge :</td>
    <td class="r-align b"><?= htmlspecialchars($invoice['reverse_charge']) ?></td>
</tr>

<tr>
    <td colspan="3" class="r-align b">TAXABLE VALUE OF SERVICES</td>
    <td class="r-align b"><?= formatIndian($invoice['amount']) ?></td>
</tr>

<tr>
    <td colspan="3" class="r-align b">CGST @ <?= $invoice['cgst_rate'] ?>%</td>
    <td class="r-align b"><?= $invoice['cgst'] ? formatIndian($invoice['cgst']) : '-' ?></td>
</tr>

<tr>
    <td colspan="3" class="r-align b">SGST @ <?= $invoice['sgst_rate'] ?>%</td>
    <td class="r-align b"><?= $invoice['sgst'] ? formatIndian($invoice['sgst']) : '-' ?></td>
</tr>

<tr>
    <td colspan="3" class="r-align b">IGST @ <?= $invoice['apply_igst'] ? $invoice['igst_rate'] : 18 ?>%</td>
    <td class="r-align b"><?= $invoice['igst'] ? formatIndian($invoice['igst']) : '-' ?></td>
</tr>

<tr>
    <td colspan="3" class="r-align b">Total Tax Amount</td>
    <td class="r-align b"><?= formatIndian($invoice['total_tax']) ?></td>
</tr>

<tr class="thick">
    <td colspan="3" class="r-align b" style="font-size:14px;">Grand Total</td>
    <td class="r-align b" style="font-size:14px;"><?= formatIndian($invoice['grand_total']) ?></td>
</tr>

    <!-- Amount in Words -->
    <tr>
    <td colspan="4" class="b" style="font-size:14px;">
        <?= htmlspecialchars($invoice['amount_words']) ?>
    </td>
</tr>

    <!-- Bank Details + Signature -->
    <tr class="thick">
    <td colspan="2" class="bank-cell">
        <strong>Please wire your remittance to :</strong><br>
        <strong>Beneficiary Bank Details</strong><br>
        <strong> <?= $supplier['name'] ?></strong><br>
        <strong> Bank Name : </strong><?= $supplier['bank_name'] ?></strong><br>
        <strong> Branch :</strong> <?= $supplier['bank_branch'] ?><br>
        <strong> Account Number : </strong><?= $supplier['account_number'] ?><br>
        <strong> IFSC : </strong><?= $supplier['ifsc_code'] ?>
    </td>
    <td colspan="2" class="sig-cell">
        <strong>Signature of the Building Owner</strong>
        <br><br><br><br>
        <strong>(<?= $supplier['name'] ?>)</strong>
    </td>
</tr>
</table>
</div>

<?php else: ?>
<!-- ════════════════════════ INPUT FORM ════════════════════════ -->

<form method="POST" action="" id="invoiceForm">
    <div class="fcard">
        <div class="ftitle">Recipient Details</div>
        <div class="fgrid">
            <div class="fgrp">
                <label>Recipient PAN <span class="rq">*</span></label>
                <input type="text" name="recipient_pan" placeholder="e.g. XXXXXXXXXX" maxlength="10" value="<?= htmlspecialchars($_POST['recipient_pan'] ?? '') ?>" required style="text-transform:uppercase">
            </div>
            <div class="fgrp">
                <label>Recipient GSTIN <span class="rq">*</span></label>
                <input type="text" name="recipient_gstin" placeholder="e.g. XXXXXXXXXX" maxlength="25" value="<?= htmlspecialchars($_POST['recipient_gstin'] ?? '') ?>" required style="text-transform:uppercase">
            </div>
            <div class="fgrp">
                <label>Company Name <span class="rq">*</span></label>
                <input type="text" name="company_name" placeholder="e.g. Company Name" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>" required style="text-transform:uppercase">
            </div>
            <div class="fgrp">
                <label>CIN Number</label>
                <input type="text" name="company_cin" placeholder="e.g. CIN Number" value="<?= htmlspecialchars($_POST['company_cin'] ?? '') ?>" style="text-transform:uppercase">
            </div>
            <div class="fgrp fw">
                <label>Company Address</label>
                <textarea name="company_address" placeholder="Company Address"><?= htmlspecialchars($_POST['company_address'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="fcard">
        <div class="ftitle">Invoice Details</div>
        <div class="fgrid">
            <div class="fgrp">
                <label>Invoice Number <span class="rq">*</span></label>
                <input type="text" name="invoice_no" placeholder="Invoice No" value="<?= htmlspecialchars($_POST['invoice_no'] ?? '') ?>" required>
            </div>
            <div class="fgrp">
                <label>Invoice Date <span class="rq">*</span></label>
                <input type="date" name="invoice_date" value="<?= htmlspecialchars($_POST['invoice_date'] ?? date('Y-m-d')) ?>" required>
            </div>
            <div class="fgrp">
                <label>Due Date <span class="rq">*</span></label>
                <input type="date" name="due_date" value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>" required>
            </div>
            <div class="fgrp">
                <label>Reverse Charge</label>
                <select name="reverse_charge">
                    <option value="No" <?= ($_POST['reverse_charge'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                    <option value="Yes" <?= ($_POST['reverse_charge'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                </select>
            </div>
            <div class="fgrp">
                <label>Invoice Period From</label>
                <input type="date" name="period_from" value="<?= htmlspecialchars($_POST['period_from'] ?? '') ?>">
            </div>
            <div class="fgrp">
                <label>Invoice Period To</label>
                <input type="date" name="period_to" value="<?= htmlspecialchars($_POST['period_to'] ?? '') ?>">
            </div>
        </div>
    </div>

    <div class="fcard">
        <div class="ftitle">Service / Item Details</div>
        <div class="fgrid">
            <div class="fgrp fw">
                <label>Particulars <span class="rq">*</span></label>
                <textarea name="particulars" placeholder='Particulars'><?= htmlspecialchars($_POST['particulars'] ?? '') ?></textarea>
            </div>
            <div class="fgrp">
                <label>SAC Code</label>
                <input type="text" name="sac_code" placeholder="SAC" value="<?= htmlspecialchars($_POST['sac_code'] ?? '') ?>">
            </div>
            <div class="fgrp">
                <label>Amount (Rs.) <span class="rq">*</span></label>
                <input type="number" name="amount" placeholder="XXXXXX" min="1" step="1" value="<?= htmlspecialchars($_POST['amount'] ?? '') ?>" required>
            </div>
        </div>
    </div>

    <div class="fcard">
        <div class="ftitle">Tax Configuration</div>
        <div class="fgrid">
            <div class="fgrp">
                <label>CGST Rate (%)</label>
                <input type="number" name="cgst_rate" value="<?= htmlspecialchars($_POST['cgst_rate'] ?? '9') ?>" min="0" max="28" step="0.5" id="cgstRate">
            </div>
            <div class="fgrp">
                <label>SGST Rate (%)</label>
                <input type="number" name="sgst_rate" value="<?= htmlspecialchars($_POST['sgst_rate'] ?? '9') ?>" min="0" max="28" step="0.5" id="sgstRate">
            </div>
            <div class="fgrp">
                <label>IGST Rate (%)</label>
                <input type="number" name="igst_rate" value="<?= htmlspecialchars($_POST['igst_rate'] ?? '18') ?>" min="0" max="28" step="0.5" id="igstRate" disabled>
            </div>
        </div>
        <div class="chkrow">
            <input type="checkbox" name="apply_igst" id="applyIgst" value="1" <?= isset($_POST['apply_igst']) ? 'checked' : '' ?>>
            <label for="applyIgst">Apply IGST instead of CGST + SGST (for inter-state transactions)</label>
        </div>
        <div class="btnrow">
            <button type="submit" class="btn btn-p">📄 Generate Invoice</button>
            <button type="reset" class="btn btn-s">↩ Reset Form</button>
        </div>
    </div>
</form>
<?php endif; ?>

</div>

<script>
    // IGST toggle
    const cb = document.getElementById('applyIgst');
    if (cb) {
        const cR = document.getElementById('cgstRate'), sR = document.getElementById('sgstRate'), iR = document.getElementById('igstRate');
        function tog() { cR.disabled = cb.checked; sR.disabled = cb.checked; iR.disabled = !cb.checked; }
        cb.addEventListener('change', tog);
        tog();
    }

    // Auto uppercase
    document.querySelectorAll('input[style*="text-transform"]').forEach(el => {
        el.addEventListener('input', function() { this.value = this.value.toUpperCase(); });
    });


</script>

</body>
</html>