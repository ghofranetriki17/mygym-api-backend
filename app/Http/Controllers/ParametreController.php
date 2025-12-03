<?php

namespace App\Http\Controllers;

use App\Models\Parametre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParametreController extends Controller
{
    // Récupérer tous les paramètres ou par groupe
    public function index(Request $request)
    {
        $groupe = $request->query('groupe');

        if ($groupe) {
            $parametres = Parametre::where('groupe', $groupe)->get();
        } else {
            $parametres = Parametre::all();
        }

        return response()->json([
            'success' => true,
            'data' => $parametres
        ]);
    }

    // Récupérer un paramètre spécifique
    public function show($cle)
    {
        $parametre = Parametre::where('cle', $cle)->first();

        if (!$parametre) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $parametre
        ]);
    }

    // Créer ou mettre à jour un paramètre
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cle' => 'required|string|max:255',
            'valeur' => 'nullable',
            'type' => 'required|in:text,textarea,image,file,json,boolean,number',
            'groupe' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $valeur = $request->valeur;
        if ($request->type === 'json' && is_array($valeur)) {
            $valeur = json_encode($valeur);
        }

        $parametre = Parametre::updateOrCreate(
            ['cle' => $request->cle],
            [
                'valeur' => $valeur,
                'type' => $request->type,
                'groupe' => $request->groupe,
                'description' => $request->description
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Paramètre enregistré avec succès',
            'data' => $parametre
        ], 201);
    }

    // Mettre à jour un paramètre
    public function update(Request $request, $id)
    {
        $parametre = Parametre::find($id);

        if (!$parametre) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'valeur' => 'nullable',
            'type' => 'sometimes|in:text,textarea,image,file,json,boolean,number',
            'groupe' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $valeur = $request->valeur;
        if ($request->type === 'json' && is_array($valeur)) {
            $valeur = json_encode($valeur);
        }

        $parametre->update([
            'valeur' => $valeur ?? $parametre->valeur,
            'type' => $request->type ?? $parametre->type,
            'groupe' => $request->groupe ?? $parametre->groupe,
            'description' => $request->description ?? $parametre->description
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paramètre mis à jour avec succès',
            'data' => $parametre
        ]);
    }

    // Supprimer un paramètre
    public function destroy($id)
    {
        $parametre = Parametre::find($id);

        if (!$parametre) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        $parametre->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paramètre supprimé avec succès'
        ]);
    }

    // Récupérer les paramètres publics (pour le frontend)
    public function public(Request $request)
    {
        $groupe = $request->query('groupe');
        
        $parametres = Parametre::getByGroupe($groupe);

        return response()->json([
            'success' => true,
            'data' => $parametres
        ]);
    }

    // Mettre à jour plusieurs paramètres en masse
    public function bulkUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parametres' => 'required|array',
            'parametres.*.cle' => 'required|string',
            'parametres.*.valeur' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->parametres as $param) {
            Parametre::set(
                $param['cle'],
                $param['valeur'],
                $param['type'] ?? 'text',
                $param['groupe'] ?? null,
                $param['description'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Paramètres mis à jour avec succès'
        ]);
    }
}