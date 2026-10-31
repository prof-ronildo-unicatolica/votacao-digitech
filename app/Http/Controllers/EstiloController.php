<?php

namespace App\Http\Controllers;

use App\Support\Contraste;
use Illuminate\View\View;

/**
 * Vitrine da identidade visual (docs/IDENTIDADE_VISUAL.md): componentes e telas de referência
 * com dados FICTÍCIOS. Serve para conferir o visual e para importar no Figma (html.to.design).
 * Só existe fora de produção (ver routes/web.php).
 */
class EstiloController extends Controller
{
    public const TELAS = [
        'urna-espera' => 'Urna · aguardando liberação',
        'urna-escolha' => 'Urna · escolha',
        'urna-confirmacao' => 'Urna · confirmação',
        'urna-registrado' => 'Urna · voto registrado',
        'mesario' => 'Painel do mesário',
        'apuracao' => 'Admin · resultado',
        'login' => 'Login',
    ];

    public function index(): View
    {
        $cores = json_decode(file_get_contents(base_path('design/tokens/tokens.json')), true)['cor'];

        $escala = fn (string $familia) => collect($cores[$familia])
            ->filter(fn ($token, $passo) => is_array($token) && ! str_starts_with($passo, '$'))
            ->map(fn ($token, $passo) => [
                'token' => "$familia.$passo",
                'hex' => $token['$value'],
                'contrasteBranco' => Contraste::razao($token['$value'], '#FFFFFF'),
                'contrastePreto' => Contraste::razao($token['$value'], '#18181A'),
            ])->values();

        return view('estilo.index', [
            'escalas' => ['laranja' => $escala('laranja'), 'grafite' => $escala('grafite')],
            'semanticas' => collect(['sucesso', 'aviso', 'perigo', 'info'])
                ->mapWithKeys(fn ($papel) => [$papel => collect($cores[$papel])->filter(fn ($t) => is_array($t))->map(fn ($t) => $t['$value'])]),
            'dados' => $escala('dados'),
            'telas' => self::TELAS,
        ]);
    }

    public function tela(string $tela): View
    {
        return view("estilo.telas.$tela", ['telas' => self::TELAS]);
    }
}
