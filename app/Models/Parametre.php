<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Parametre extends Model
{
    use HasFactory;

    protected $fillable = [
        'cle',
        'valeur',
        'type',
        'groupe',
        'description'
    ];

    protected $casts = [
        'valeur' => 'string',
    ];

    // Méthode statique pour récupérer un paramètre par clé
    public static function get(string $cle, $default = null)
    {
        return Cache::remember("parametre_{$cle}", 3600, function () use ($cle, $default) {
            $parametre = self::where('cle', $cle)->first();
            
            if (!$parametre) {
                return $default;
            }

            // Conversion selon le type
            return match($parametre->type) {
                'json' => json_decode($parametre->valeur, true),
                'boolean' => filter_var($parametre->valeur, FILTER_VALIDATE_BOOLEAN),
                'number' => (int) $parametre->valeur,
                default => $parametre->valeur
            };
        });
    }

    // Méthode pour définir un paramètre
    public static function set(string $cle, $valeur, string $type = 'text', ?string $groupe = null, ?string $description = null)
    {
        $valeurFormatee = is_array($valeur) || is_object($valeur) ? json_encode($valeur) : $valeur;

        $parametre = self::updateOrCreate(
            ['cle' => $cle],
            [
                'valeur' => $valeurFormatee,
                'type' => $type,
                'groupe' => $groupe,
                'description' => $description
            ]
        );

        Cache::forget("parametre_{$cle}");
        Cache::forget('all_parametres');

        return $parametre;
    }

    // Récupérer tous les paramètres par groupe
    public static function getByGroupe(?string $groupe = null)
    {
        $cacheKey = $groupe ? "parametres_groupe_{$groupe}" : 'all_parametres';

        return Cache::remember($cacheKey, 3600, function () use ($groupe) {
            $query = self::query();
            
            if ($groupe) {
                $query->where('groupe', $groupe);
            }

            return $query->get()->mapWithKeys(function ($param) {
                $valeur = match($param->type) {
                    'json' => json_decode($param->valeur, true),
                    'boolean' => filter_var($param->valeur, FILTER_VALIDATE_BOOLEAN),
                    'number' => (int) $param->valeur,
                    default => $param->valeur
                };

                return [$param->cle => $valeur];
            });
        });
    }

    // Vider le cache lors de la sauvegarde
    protected static function booted()
    {
        static::saved(function ($parametre) {
            Cache::forget("parametre_{$parametre->cle}");
            Cache::forget('all_parametres');
            if ($parametre->groupe) {
                Cache::forget("parametres_groupe_{$parametre->groupe}");
            }
        });

        static::deleted(function ($parametre) {
            Cache::forget("parametre_{$parametre->cle}");
            Cache::forget('all_parametres');
            if ($parametre->groupe) {
                Cache::forget("parametres_groupe_{$parametre->groupe}");
            }
        });
    }
}