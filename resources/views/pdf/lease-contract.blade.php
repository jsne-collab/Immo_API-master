<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Contrat de location #{{ $lease->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1A1A1A; }
        h1 { font-size: 18px; color: #0A2540; margin-top: 16px; }
        h2 { font-size: 14px; color: #0A2540; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        td { padding: 4px 0; vertical-align: top; }
        .label { color: #6B7280; width: 220px; }
        .footer { margin-top: 40px; font-size: 11px; color: #6B7280; }
    </style>
</head>
<body>
    @include('pdf.partials.header')

    <h1>Contrat de location</h1>

    <h2>Bien loué</h2>
    <table>
        <tr><td class="label">Titre</td><td>{{ $property->title }}</td></tr>
        <tr><td class="label">Adresse</td><td>{{ $property->address }}, {{ $property->city }}</td></tr>
        @if ($unit)
            <tr><td class="label">Unité</td><td>{{ $unit->unit_name }}</td></tr>
        @endif
    </table>

    <h2>Parties</h2>
    <table>
        <tr><td class="label">Propriétaire (bailleur)</td><td>{{ $owner->name }} — {{ $owner->phone }} — {{ $owner->email }}</td></tr>
        <tr><td class="label">Locataire (preneur)</td><td>{{ $tenant->name }} — {{ $tenant->phone }} — {{ $tenant->email }}</td></tr>
    </table>

    <h2>Conditions</h2>
    <table>
        <tr><td class="label">Date de début</td><td>{{ $lease->start_date->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Date de fin</td><td>{{ $lease->end_date->format('d/m/Y') }}</td></tr>
        <tr><td class="label">Loyer mensuel</td><td>{{ number_format($lease->monthly_rent, 0, ',', ' ') }} FCFA</td></tr>
        <tr><td class="label">Dépôt de garantie (caution)</td><td>{{ number_format($lease->deposit_amount, 0, ',', ' ') }} FCFA</td></tr>
    </table>

    <p class="footer">
        Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }} — Gestion Locative.
    </p>
</body>
</html>
