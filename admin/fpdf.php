<?php
// admin/fpdf.php

$lib = __DIR__ . '/fpdf186/fpdf.php';
if (!file_exists($lib)) {
    throw new Exception('Librairie FPDF introuvable dans admin/fpdf186/fpdf.php');
}
require_once $lib;
if (!class_exists('FPDF')) {
    throw new Exception('Classe FPDF non chargée après inclusion.');
}

/**
 * Classe d'extension FPDF pour ajouter le logo et le Header
 */
class NOVALOC_PDF extends FPDF
{
    function euro() { return chr(128); }

    function Header()
    {
        // Affiche le logo en haut à droite
        $logoPath = __DIR__ . '/../pdf/logo.png';
        if (file_exists($logoPath)) {
            // largeur 50mm, positionné à droite (x=150)
            $this->Image($logoPath, 10, 10, 50);
        }
        $this->SetFont('Arial', '', 10);
        // Décale les infos société sous le logo
        $this->SetXY(120, 36); // 36 = 10 (y du logo) + 26 (hauteur estimée du logo)
        $this->Cell(80, 5, utf8_decode('NOVALOC'), 0, 1, 'R');
        $this->SetX(120);
        $this->Cell(80, 5, utf8_decode('31 rue des locations'), 0, 1, 'R');
        $this->SetX(120);
        $this->Cell(80, 5, utf8_decode('12345 NOVALOC'), 0, 1, 'R');
        $this->SetX(120);
        $this->Cell(80, 5, utf8_decode('SIRET: 123 456 789 00010'), 0, 1, 'R');
        $this->SetY(46);
    }
}

/**
 * Génère une facture PDF pour une réservation
 */
function generateInvoice(int $reservationId, PDO $pdo): string
{
    $stmt = $pdo->prepare(
        "SELECT r.*, u.username, u.email, c.marque, c.modele, c.prix AS car_prix
         FROM reservations r
         JOIN users u ON r.user_id = u.id
         JOIN cars c ON r.car_id = c.id
         WHERE r.id = ?"
    );
    $stmt->execute([$reservationId]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$res) {
        throw new Exception('Réservation introuvable.');
    }

    // Abréviation du modèle (enlève "Autobiography" et tout après)
    $modeleBrut = trim($res['modele']);
    $modeleAbrege = preg_replace('/\s*Autobiography.*/i', '', $modeleBrut);
    $modeleAbrege = trim($modeleAbrege);
    $modeleFichier = preg_replace('/[^a-zA-Z0-9]/', '', $modeleAbrege);

    $date  = date('d/m/Y');
    $dir = __DIR__ . '/../pdf/files';
    if (!is_dir($dir)) {
        if (!mkdir($dir, 0755, true)) {
            throw new Exception("Impossible de créer le dossier $dir");
        }
    }
    $file = $modeleFichier . '.pdf';
    $path = "{$dir}/{$file}";

    $ttc = (float)$res['payment_amount'];
    $ht  = round($ttc / 1.2, 2);
    $tva = round($ttc - $ht, 2);

    $pdf = new NOVALOC_PDF();
    $euro = chr(128);
    $pdf->AddPage();

    // Titre
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, utf8_decode('FACTURE'), 0, 1, 'C');
    $pdf->Ln(2);

    // Numéro & date (on met le modèle abrégé)
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(60, 8, utf8_decode($modeleAbrege), 1, 0, 'C');
    $pdf->Cell(40, 8, utf8_decode($date), 1, 1, 'C');
    $pdf->Ln(4);

    // Client / À L'ATTENTION DE
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(95, 6, utf8_decode('CLIENT'), 0, 0);
    $pdf->Cell(95, 6, utf8_decode("À L'ATTENTION DE"), 0, 1);
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(95, 5, utf8_decode($res['username']), 0, 0);
    $pdf->Cell(95, 5, utf8_decode('NOVALOC LUXURY CAR RENTAL'), 0, 1);
    $pdf->Cell(95, 5, utf8_decode($res['email']), 0, 0);
    $pdf->Cell(95, 5, utf8_decode('31 rue des locations'), 0, 1);
    $pdf->Cell(95, 5, '', 0, 0);
    $pdf->Cell(95, 5, utf8_decode('12345 NOVALOC'), 0, 1);
    $pdf->Ln(6);

    // Séparateur
    $y = $pdf->GetY();
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, $y, 200, $y);
    $pdf->Ln(8);

    // Prix par mois / total / HT / TVA
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 8, utf8_decode('Prix par mois :'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, number_format($res['car_prix'], 2, ',', ' ') . " $euro", 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 8, utf8_decode('Prix total TTC :'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, number_format($ttc, 2, ',', ' ') . " $euro", 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 8, utf8_decode('Prix HT :'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, number_format($ht, 2, ',', ' ') . " $euro", 0, 1);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(60, 8, utf8_decode('Montant TVA :'), 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, number_format($tva, 2, ',', ' ') . " $euro", 0, 1);
    $pdf->Ln(6);

    // Tableau de la prestation
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetFillColor(200);
    $pdf->Cell(90, 8, utf8_decode('DESCRIPTION'), 1, 0, 'C', true);
    $pdf->Cell(30, 8, utf8_decode('PRIX HT'), 1, 0, 'C', true);
    $pdf->Cell(20, 8, utf8_decode('QTÉ'), 1, 0, 'C', true);
    $pdf->Cell(30, 8, utf8_decode('TOTAL HT'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 10);
    $desc = utf8_decode($res['marque'].' '.$modeleAbrege.', '
          . date('d/m/Y', strtotime($res['start_date']))
          . ' - '
          . date('d/m/Y', strtotime($res['end_date'])));
    $pdf->Cell(90, 6, $desc, 1);
    $pdf->Cell(30, 6, number_format($ht, 2, ',', ' ') . " $euro", 1, 0, 'R');
    $pdf->Cell(20, 6, '1', 1, 0, 'C');
    $pdf->Cell(30, 6, number_format($ht, 2, ',', ' ') . " $euro", 1, 1, 'R');
    $pdf->Ln(4);

    // Totaux
    $pdf->Cell(140, 6, utf8_decode('Sous-total HT :'), 0, 0, 'R');
    $pdf->Cell(30, 6, number_format($ht, 2, ',', ' ') . " $euro", 0, 1, 'R');
    $pdf->Cell(140, 6, utf8_decode('TVA (20%) :'), 0, 0, 'R');
    $pdf->Cell(30, 6, number_format($tva, 2, ',', ' ') . " $euro", 0, 1, 'R');
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(140, 8, utf8_decode('TOTAL TTC :'), 0, 0, 'R');
    $pdf->Cell(30, 8, number_format($ttc, 2, ',', ' ') . " $euro", 0, 1, 'R');

    // Mention légale TVA
    $pdf->Ln(4);
    $pdf->SetFont('Arial', 'I', 9);
    $pdf->Cell(0, 5, utf8_decode('TVA non applicable, art. 293B du CGI'), 0, 1, 'L');

    // Bas de page - Informations de paiement
    $pdf->Ln(8);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 6, utf8_decode('INFORMATIONS DE PAIEMENT'), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 9);
    $pdf->Cell(0, 5, utf8_decode("Paiement à l'ordre de NOVALOC LUXURY CAR RENTAL"), 0, 1);
    $pdf->Cell(0, 5, utf8_decode("IBAN: FR76 1234 5678 9876 5432 1000 123"), 0, 1);
    $pdf->Cell(0, 5, utf8_decode("BIC: AGRIFPPXXX"), 0, 1);
    $pdf->Cell(0, 5, utf8_decode("Carte Bancaire: VISA / MASTERCARD / AMEX acceptées"), 0, 1);
    $pdf->Cell(0, 5, utf8_decode('Conditions : Paiement à 30 jours'), 0, 1);

    // Enregistrement du PDF
    $pdf->Output('F', $path);
    return $file;
}
