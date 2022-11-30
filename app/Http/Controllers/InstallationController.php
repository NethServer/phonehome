<?php

#
# Copyright (C) 2022 Nethesis S.r.l.
# SPDX-License-Identifier: AGPL-3.0-or-later
#

namespace App\Http\Controllers;

use App\Http\Requests\IndexInstallationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class InstallationController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexInstallationRequest $request): JsonResponse
    {
        // retro-compatible query
        $query = DB::table('countries')
            ->selectRaw('countries.name as country_name, countries.code as country_code, versions.tag, COUNT(installations.uuid) as num')
            ->join('installations', 'installations.country_id', '=', 'countries.id')
            ->join('versions', 'versions.id', '=', 'installations.version_id');
        if ($request->get('interval') != '1') {
            $query = $query->whereRaw('installations.updated_at > \'' . today()->subDays($request->get('interval'))->toDateString() . '\'');
        }
        $query = $query->groupBy('countries.name', 'countries.code', 'versions.tag')
            ->orderBy('versions.tag');
        $query = DB::table(DB::raw('(' . $query->toSql() . ') as base'))
            ->select('country_name', 'country_code', DB::raw('array_to_string(array_agg(concat( tag, \'#\', num )), \',\') AS installations'))
            ->groupBy('country_name', 'country_code')
            ->orderBy('country_code');

        return response()->json(
            $query->get()
        );
    }
}
