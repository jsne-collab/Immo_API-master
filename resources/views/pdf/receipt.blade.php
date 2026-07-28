<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Quittance {{ $receiptNumber }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1A1A1A; }
        h1 { font-size: 18px; color: #0A2540; margin-top: 16px; }
        .receipt-number { color: #C9A227; font-weight: bold; font-size: 15px; margin-top: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        td { padding: 4px 0; vertical-align: top; }
        .label { color: #6B7280; width: 220px; }
        .amount { font-size: 20px; color: #0A2540; font-weight: bold; margin-top: 16px; }
        .footer { margin-top: 40px; font-size: 11px; color: #6B7280; }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <h1>Quittance de loyer</h1>
    <div class="receipt-number">N° {{ $receiptNumber }}</div>

    <table>
        <tr><td class="label">Bien</td><td>{{ $property->title }}</td></tr>
        <tr><td class="label">Adresse</td><td>{{ $property->address }}, {{ $property->city }}</td></tr>
        <tr><td class="label">Locataire</td><td>{{ $tenant->name }} — {{ $tenant->phone }}</td></tr>
        <tr><td class="label">Propriétaire</td><td>{{ $owner->name }} — {{ $owner->phone }}</td></tr>
        <tr><td class="label">Période couverte</td><td>{{ $payment->period_covered }}</td></tr>
        <tr><td class="label">Date du paiement</td><td>{{ $payment->payment_date->format('d/m/Y') }}</td></tr>
        @if ($payment->reference)
            <tr><td class="label">Référence</td><td>{{ $payment->reference }}</td></tr>
        @endif
    </table>

    <div class="amount">Montant reçu : {{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>

    <p class="footer">
        Quittance générée automatiquement le {{ now()->format('d/m/Y à H:i') }} — Gestion Locative.
        Ce document atteste du paiement du loyer pour la période mentionnée ci-dessus.
    </p>
</body>
</html>
