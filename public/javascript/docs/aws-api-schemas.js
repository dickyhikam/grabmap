/**
 * Skema field per operasi untuk Request Builder di /docs/aws-api.
 *
 * Satu operasi = satu entri di sini. Renderer-nya (aws-api-builder.js) tidak
 * tahu apa-apa soal SearchText atau Places — semua yang khas operasi ditulis
 * di berkas ini, jadi menambah operasi berikutnya cukup menambah entri baru.
 *
 * Semua batas dan catatan region di bawah hasil uji langsung ke
 * ap-southeast-1 (3 September 2026), bukan salinan dokumen AWS — beberapa di
 * antaranya memang berbeda dari yang tertulis di sana.
 *
 * Bentuk grup:
 *   one-or-more   minimal satu harus dicentang
 *   exactly-one   tepat satu (dirender sebagai radio)
 *   any           bebas
 *   multi         daftar nilai untuk satu field array
 *
 * Bentuk field:
 *   string | number | enum | lnglat | bbox | circle | list
 */
(function (window) {
    'use strict';

    window.AWSAPI_SCHEMAS = {
        'places-search-text': {
            tryPrefix: 'st',
            baseTier: 'Core',
            groups: [
                {
                    id: 'query',
                    kind: 'required',
                    titleKey: 'bld_g_query',
                    // QueryId, PoliticalView, NextToken, dan tiga AdditionalFeatures
                    // lain tidak dijadikan kontrol karena ditolak atau tidak pernah bisa
                    // didapat di ap-southeast-1 — alasannya ada di daftar Region Caveats
                    // pada panel pembuka.
                    fields: [
                        { name: 'QueryText', type: 'string', value: 'monas jakarta', on: true, required: true, maxLength: 200, noteKey: 'bld_n_querytext' }
                    ]
                },
                {
                    id: 'spatial',
                    kind: 'exactly-one',
                    titleKey: 'bld_g_spatial',
                    warnKey: 'bld_warn_spatial',
                    fields: [
                        { name: 'BiasPosition', type: 'lnglat', value: [106.8272, -6.1751], on: true, noteKey: 'bld_n_bias' },
                        { name: 'Filter.BoundingBox', type: 'bbox', value: [106.6, -6.4, 107.0, -6.0], noteKey: 'st_p_bbox' },
                        { name: 'Filter.Circle', type: 'circle', value: { Center: [106.8272, -6.1751], Radius: 5000 }, max: 50000, noteKey: 'bld_n_circle' }
                    ]
                },
                {
                    id: 'optional',
                    kind: 'any',
                    titleKey: 'bld_g_optional',
                    fields: [
                        { name: 'MaxResults', type: 'number', value: 20, min: 1, max: 100, on: true, noteKey: 'bld_n_maxresults' },
                        { name: 'Filter.IncludeCountries', type: 'list', value: 'IDN', noteKey: 'note_iso3_arr' },
                        { name: 'Language', type: 'string', value: 'id', noteKey: 'note_bcp47' },
                        {
                            name: 'IntendedUse',
                            type: 'enum',
                            options: ['SingleUse', 'Storage'],
                            value: 'SingleUse',
                            tierWhen: { Storage: 'Stored' },
                            noteKey: 'bld_note_intended'
                        }
                    ]
                },
                {
                    id: 'features',
                    kind: 'multi',
                    field: 'AdditionalFeatures',
                    titleKey: 'bld_g_features',
                    options: [
                        { value: 'TimeZone', tier: 'Advanced' }
                    ]
                }
            ],

            /**
             * Blok respons yang hanya muncul kalau field tertentu dikirim.
             * Dipakai untuk menandai bagian Response Syntax di bawah builder.
             */
            responseHints: [
                { when: { additionalFeature: 'TimeZone' }, block: 'TimeZone', descKey: 'bld_resp_timezone' },
                { when: { field: 'BiasPosition' }, block: 'Distance', descKey: 'bld_resp_distance' }
            ]
        },

        'places-suggest': {
            tryPrefix: 'sg',
            // Tanpa AdditionalFeatures, balasannya cuma PlaceId/PlaceType/Address —
            // itulah keranjang Label, yang termurah. Terbukti dari balasan asli.
            baseTier: 'Label',
            groups: [
                {
                    id: 'query',
                    kind: 'required',
                    titleKey: 'bld_g_query',
                    // QueryId sebenarnya diterima Suggest (beda dari SearchText yang
                    // menolaknya bila QueryText ikut), tapi tidak pernah bisa didapat
                    // karena item bertipe Query butuh MaxQueryRefinements yang ditolak
                    // region ini. PoliticalView juga 400.
                    fields: [
                        { name: 'QueryText', type: 'string', value: 'mon', on: true, required: true, maxLength: 200, noteKey: 'sg_n_querytext' }
                    ]
                },
                {
                    id: 'spatial',
                    kind: 'exactly-one',
                    titleKey: 'bld_g_spatial',
                    warnKey: 'bld_warn_spatial',
                    fields: [
                        { name: 'BiasPosition', type: 'lnglat', value: [106.8272, -6.1751], on: true, noteKey: 'sg_n_bias' },
                        { name: 'Filter.BoundingBox', type: 'bbox', value: [106.6, -6.4, 107.0, -6.0], noteKey: 'st_p_bbox' },
                        { name: 'Filter.Circle', type: 'circle', value: { Center: [106.8272, -6.1751], Radius: 5000 }, max: 50000, noteKey: 'bld_n_circle' }
                    ]
                },
                {
                    id: 'optional',
                    kind: 'any',
                    titleKey: 'bld_g_optional',
                    fields: [
                        { name: 'MaxResults', type: 'number', value: 5, min: 1, max: 100, on: true, noteKey: 'sg_n_maxresults' },
                        { name: 'Filter.IncludeCountries', type: 'list', value: 'IDN', noteKey: 'note_iso3_arr' },
                        { name: 'Language', type: 'string', value: 'id', noteKey: 'note_bcp47' }
                    ]
                },
                {
                    id: 'features',
                    kind: 'multi',
                    field: 'AdditionalFeatures',
                    titleKey: 'bld_g_features',
                    options: [
                        { value: 'Core', tier: 'Core' },
                        { value: 'TimeZone', tier: 'Advanced' }
                    ]
                }
            ],

            responseHints: [
                { when: { additionalFeature: 'Core' }, block: 'Position, Distance, MapView, Categories, Highlights' },
                { when: { additionalFeature: 'TimeZone' }, block: 'TimeZone' }
            ]
        },

        'places-reverse-geocode': {
            tryPrefix: 'rg',
            baseTier: 'Core',
            groups: [
                {
                    id: 'query',
                    kind: 'required',
                    titleKey: 'rg_g_query',
                    fields: [
                        { name: 'QueryPosition', type: 'lnglat', value: [106.8272, -6.1751], on: true, required: true, noteKey: 'rg_n_qpos' }
                    ]
                },
                {
                    id: 'optional',
                    kind: 'any',
                    titleKey: 'bld_g_optional',
                    // Filter.IncludePlaceTypes dan PoliticalView tidak dijadikan kontrol:
                    // keduanya membalas 400 di ap-southeast-1.
                    fields: [
                        { name: 'QueryRadius', type: 'number', value: 500, min: 1, max: 100000, noteKey: 'rg_n_radius' },
                        { name: 'MaxResults', type: 'number', value: 1, min: 1, max: 100, on: true, noteKey: 'rg_n_maxresults' },
                        { name: 'Filter.IncludeCountries', type: 'list', value: 'IDN', noteKey: 'note_iso3_arr' },
                        { name: 'Language', type: 'string', value: 'id', noteKey: 'note_bcp47' },
                        {
                            name: 'IntendedUse',
                            type: 'enum',
                            options: ['SingleUse', 'Storage'],
                            value: 'SingleUse',
                            tierWhen: { Storage: 'Stored' },
                            noteKey: 'bld_note_intended'
                        }
                    ]
                },
                {
                    id: 'features',
                    kind: 'multi',
                    field: 'AdditionalFeatures',
                    titleKey: 'bld_g_features',
                    options: [
                        { value: 'TimeZone', tier: 'Advanced' }
                    ]
                }
            ],

            responseHints: [
                { when: { additionalFeature: 'TimeZone' }, block: 'TimeZone' }
            ]
        },

        'places-get-place': {
            tryPrefix: 'gp',
            baseTier: 'Core',
            // GetPlace satu-satunya operasi Places yang GET: parameternya di path dan
            // query string, bukan badan JSON.
            transport: 'query',
            pathTemplate: 'GET https://places.geo.{region}.amazonaws.com/v2/place/{PlaceId}',
            featureQuery: 'additional-features',
            // Panel ini punya formulir sendiri (bukan editor JSON), jadi tombol kirim
            // mengisi kotaknya lalu menekan #gp-run.
            sendInputs: { 'gp-id': 'PlaceId', 'gp-lang': 'language', 'gp-feat': '@features' },
            groups: [
                {
                    id: 'place',
                    kind: 'required',
                    titleKey: 'gp_g_place',
                    fields: [
                        { name: 'PlaceId', type: 'string', value: '', inPath: true, on: true, required: true, placeholder: 'dari SearchText / Suggest', noteKey: 'gp_n_placeid' }
                    ]
                },
                {
                    id: 'optional',
                    kind: 'any',
                    titleKey: 'bld_g_optional',
                    // political-view dan intended-use tidak dijadikan kontrol: yang
                    // pertama 400 di region ini, yang kedua hanya menerima SingleUse
                    // yang sudah jadi bawaan (Storage 400).
                    fields: [
                        { name: 'language', query: 'language', type: 'string', value: 'id', noteKey: 'note_bcp47' }
                    ]
                },
                {
                    id: 'features',
                    kind: 'multi',
                    field: 'AdditionalFeatures',
                    titleKey: 'bld_g_features',
                    options: [
                        { value: 'TimeZone', tier: 'Advanced' }
                    ]
                }
            ],

            responseHints: [
                { when: { additionalFeature: 'TimeZone' }, block: 'TimeZone' }
            ]
        },

        'routes-calculate-routes': {
            tryPrefix: 'cr',
            // Pemicu Premium (tol dan Intermodal) semuanya ditolak region ini, jadi
            // keranjangnya tidak pernah beranjak dari Core untuk pelanggan GrabMaps.
            baseTier: 'Core',
            groups: [
                {
                    id: 'points',
                    kind: 'required',
                    titleKey: 'cr_g_points',
                    fields: [
                        { name: 'Origin', type: 'lnglat', value: [106.8272, -6.1751], on: true, required: true },
                        { name: 'Destination', type: 'lnglat', value: [106.8451, -6.2088], on: true, required: true }
                    ]
                },
                {
                    id: 'options',
                    kind: 'any',
                    titleKey: 'bld_g_optional',
                    fields: [
                        { name: 'TravelMode', type: 'enum', options: ['Car', 'Scooter', 'Pedestrian'], value: 'Car', noteKey: 'cr_n_mode' },
                        { name: 'DepartureTime', type: 'string', value: '2026-09-04T08:00:00Z', noteKey: 'cr_n_departure' },
                        { name: 'OptimizeRoutingFor', type: 'enum', options: ['FastestRoute', 'ShortestRoute'], value: 'FastestRoute' },
                        { name: 'MaxAlternatives', type: 'number', value: 2, min: 0, max: 5, noteKey: 'cr_n_alternatives' },
                        { name: 'TravelStepType', type: 'enum', options: ['Default', 'TurnByTurn'], value: 'TurnByTurn', noteKey: 'cr_n_steptype' },
                        { name: 'LegGeometryFormat', type: 'enum', options: ['Simple', 'FlexiblePolyline'], value: 'Simple' },
                        { name: 'InstructionsMeasurementSystem', type: 'enum', options: ['Metric', 'Imperial'], value: 'Metric', noteKey: 'cr_n_units' },
                        { name: 'Locale', type: 'string', value: 'id' }
                    ]
                },
                {
                    id: 'avoid',
                    kind: 'any',
                    titleKey: 'cr_g_avoid',
                    // Hanya tiga ini yang dilayani; DirtRoads, Tunnels, UTurns, dan Areas
                    // membalas 400 di ap-southeast-1.
                    fields: [
                        { name: 'Avoid.TollRoads', type: 'bool' },
                        { name: 'Avoid.Ferries', type: 'bool' },
                        { name: 'Avoid.ControlledAccessHighways', type: 'bool' }
                    ]
                }
            ]
        },

        'routes-calculate-route-matrix': {
            tryPrefix: 'crm',
            baseTier: 'Core',
            groups: [
                {
                    id: 'points',
                    kind: 'required',
                    titleKey: 'crm_g_points',
                    fields: [
                        { name: 'Origins', type: 'poslist', value: '106.8272,-6.1751; 106.8000,-6.2000', on: true, required: true, noteKey: 'crm_n_origins' },
                        { name: 'Destinations', type: 'poslist', value: '106.8451,-6.2088; 106.9000,-6.1500', on: true, required: true, noteKey: 'crm_n_destinations' }
                    ]
                },
                {
                    id: 'options',
                    kind: 'any',
                    titleKey: 'bld_g_optional',
                    fields: [
                        { name: 'TravelMode', type: 'enum', options: ['Car', 'Scooter', 'Pedestrian'], value: 'Car', noteKey: 'cr_n_mode' },
                        { name: 'RoutingBoundary.Unbounded', type: 'bool', on: true, noteKey: 'crm_n_boundary' },
                        { name: 'DepartureTime', type: 'string', value: '2026-09-04T08:00:00Z', noteKey: 'cr_n_departure' },
                        { name: 'OptimizeRoutingFor', type: 'enum', options: ['FastestRoute', 'ShortestRoute'], value: 'FastestRoute' },
                        { name: 'Avoid.TollRoads', type: 'bool' },
                        { name: 'Avoid.Ferries', type: 'bool' }
                    ]
                }
            ]
        }
    };
})(window);
