/*
* DatafilterConfig.js
*
* Copyright (c) 2022, Kathrin Marquardt. All rights reserved.
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU Lesser General Public
* License as published by the Free Software Foundation; either
* version 3 of the License, or (at your option) any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
* Lesser General Public License for more details.
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
* MA 02110-1301  USA
*/

/**
 * @class DatafilterConfig
 * Configuration file for user / data file defined filter
 * @author Kathrin Marquardt (Kathrin.Marquardt@sbb.spk-berlin.de)
 * @release 1.0
 * @release date: 2022-01-19
 * @version date: 2022-01-19
 */
function DatafilterConfig() {

    this.dataFilterOpt = new Array();
// structure - formular element name = json section, label text, data files column name
    this.dataFilterOpt[0] = new Array( 'fsWerke', 'Werke', 'fshop', 'Works');
    this.dataFilterOpt[1] = new Array( 'isoLang', 'Sprache', 'iso2Sprache', 'Language');
    this.dataFilterOpt[2] = new Array( 'wikiCountry', 'Land', 'isoLand', 'Country');
    this.labelLang = 'de';

    this.options = {
        fsWerke : [
            {
                "value": "a1",
                "label": "Gesamtausgaben"
            },
            {
                "value": "a2a",
                "label": "Sammelbände"
            },
            {
                "value": "M",
                "label": "Musikalische Schriften/Aufsätze"
            },
            {
                "value": "AH",
                "label": "Der Artushof"
            },
            {
                "value": "AM",
                "label": "Die Automate"
            },
            {
                "value": "AS",
                "label": "Die Abenteuer der Sylvester-Nacht"
            },
            {
                "value": "AT",
                "label": "Die ästhetische Teegesellschaft"
            },
            {
                "value": "BB",
                "label": "Der Baron von B."
            },
            {
                "value": "BF",
                "label": "Die Bergwerke zu Falun"
            },
            {
                "value": "BR",
                "label": "Die Brautwahl"
            },
            {
                "value": "DD",
                "label": "Doge und Dogaresse"
            },
            {
                "value": "DE",
                "label": "Der Dey von Elba in Paris"
            },
            {
                "value": "DF",
                "label": "Datura fastuosa"
            },
            {
                "value": "DG",
                "label": "Die Doppeltgänger"
            },
            {
                "value": "DJ",
                "label": "Don Juan"
            },
            {
                "value": "DK",
                "label": "Der Dichter und der Komponist"
            },
            {
                "value": "EG",
                "label": "Der Elementargeist"
            },
            {
                "value": "ER",
                "label": "Erscheinungen"
            },
            {
                "value": "ES",
                "label": "Der Einsiedler Serapion"
            },
            {
                "value": "FS",
                "label": "Das Fräulein von Scuderi"
            },
            {
                "value": "ET",
                "label": "Die Elixiere des Teufels"
            },
            {
                "value": "FD",
                "label": "Der Feind"
            },
            {
                "value": "FK",
                "label": "Das fremde Kind"
            },
            {
                "value": "FM",
                "label": "Die Fermate"
            },
            {
                "value": "FC",
                "label": "Fantasiestücke in Callot’s Manier"
            },
            {
                "value": "FL",
                "label": "Ein Fragment aus dem Leben dreier Freunde"
            },
            {
                "value": "GE",
                "label": "Die Genesung"
            },
            {
                "value": "GL",
                "label": "Das Gelübde"
            },
            {
                "value": "GT",
                "label": "Der goldene Topf"
            },
            {
                "value": "HA",
                "label": "Haimatochare"
            },
            {
                "value": "ID",
                "label": "Ignaz Denner"
            },
            {
                "value": "IG",
                "label": "Die Irrungen / Die Geheimnisse"
            },
            {
                "value": "JG",
                "label": "Die Jesuiterkirche in G."
            },
            {
                "value": "KB",
                "label": "Die Königsbraut"
            },
            {
                "value": "KR",
                "label": "Kreisleriana"
            },
            {
                "value": "KS",
                "label": "Der Kampf der Sänger"
            },
            {
                "value": "KV",
                "label": "Die Kunstverwandten"
            },
            {
                "value": "KZ",
                "label": "Klein Zaches genannt Zinnober"
            },
            {
                "value": "LM",
                "label": "Lebens-Ansichten des Katers Murr"
            },
            {
                "value": "MF",
                "label": "Meister Floh"
            },
            {
                "value": "MG",
                "label": "Der Magnetiseur"
            },
            {
                "value": "MJ",
                "label": "Das Majorat"
            },
            {
                "value": "MM",
                "label": "Meister Martin der Küfner und seine Gesellen"
            },
            {
                "value": "MP",
                "label": "Die Marquise de la Pivardiere"
            },
            {
                "value": "MW",
                "label": "Meister Johannes Wacht"
            },
            {
                "value": "NA",
                "label": "Nachtstücke"
            },
            {
                "value": "NB",
                "label": "Nachricht von den neuesten Schicksalen des Hundes Berganza"
            },
            {
                "value": "NE",
                "label": "Neueste Schicksale eines abenteuerlichen Mannes"
            },
            {
                "value": "NL",
                "label": "Nachricht aus dem Leben eines bekannten Mannes"
            },
            {
                "value": "NM",
                "label": "Nußknacker und Mausekönig"
            },
            {
                "value": "NT",
                "label": "Naivität"
            },
            {
                "value": "OH",
                "label": "Das öde Haus"
            },
            {
                "value": "PB",
                "label": "Prinzessin Brambilla"
            },
            {
                "value": "PL",
                "label": "Prinzessin Blandina"
            },
            {
                "value": "RA",
                "label": "Die Räuber"
            },
            {
                "value": "RG",
                "label": "Ritter Gluck"
            },
            {
                "value": "RK",
                "label": "Rat Krespel"
            },
            {
                "value": "SA",
                "label": "Das Sanctus"
            },
            {
                "value": "SB",
                "label": "Die Serapions-Brüder"
            },
            {
                "value": "SF",
                "label": "Signor Formica"
            },
            {
                "value": "SG",
                "label": "Spielerglück"
            },
            {
                "value": "SH",
                "label": "Das steinerne Herz"
            },
            {
                "value": "SP",
                "label": "Eine Spukgeschichte"
            },
            {
                "value": "SM",
                "label": "Der Sandmann"
            },
            {
                "value": "ST",
                "label": "Seltsame Leiden eines Theater-Direktors"
            },
            {
                "value": "UG",
                "label": "Der unheimliche Gast"
            },
            {
                "value": "VA",
                "label": "Vampirismus"
            },
            {
                "value": "VE",
                "label": "Des Vetters Eckfenster"
            },
            {
                "value": "VS",
                "label": "Die Vision auf dem Schlachtfelde bei Dresden"
            },
            {
                "value": "ZD",
                "label": "Der Zusammenhang der Dinge"
            },
            {
                "value": "ZZ",
                "label": "Sonstige Werke"
            }
        ],
        isoLang :[
            {
                "value": "afr",
                "label_de": "Afrikaans",
                "label_iso": "Afrikaans"
            },
            {
                "value": "alb",
                "label_de": "Albanisch",
                "label_iso": "Albanian"
            },
            {
                "value": "ara",
                "label_de": "Arabisch",
                "label_iso": "Arabic"
            },
            {
                "value": "arm",
                "label_de": "Armenisch",
                "label_iso": "Armenian"
            },
            {
                "value": "baq",
                "label_de": "Baskisch",
                "label_iso": "Basque"
            },
            {
                "value": "bul",
                "label_de": "Bulgarisch",
                "label_iso": "Bulgarian"
            },
            {
                "value": "cat",
                "label_de": "Katalanisch, Valencianisch",
                "label_iso": "Catalan; Valencian"
            },
            {
                "value": "chi",
                "label_de": "Chinesisch",
                "label_iso": "Chinese"
            },
            {
                "value": "cze",
                "label_de": "Tschechisch",
                "label_iso": "Czech"
            },
            {
                "value": "dan",
                "label_de": "Dänisch",
                "label_iso": "Danish"
            },
            {
                "value": "dut",
                "label_de": "Niederländisch, Belgisches Niederländisch",
                "label_iso": "Dutch; Flemish"
            },
            {
                "value": "eng",
                "label_de": "Englisch",
                "label_iso": "English"
            },
            {
                "value": "epo",
                "label_de": "Esperanto",
                "label_iso": "Esperanto"
            },
            {
                "value": "est",
                "label_de": "Estnisch",
                "label_iso": "Estonian"
            },
            {
                "value": "fao",
                "label_de": "Färöisch",
                "label_iso": "Faroese"
            },
            {
                "value": "fin",
                "label_de": "Finnisch",
                "label_iso": "Finnish"
            },
            {
                "value": "fre",
                "label_de": "Französisch",
                "label_iso": "French"
            },
            {
                "value": "gem",
                "label_de": "Germanische Sprachen",
                "label_iso": "Germanic languages"
            },
            {
                "value": "ger",
                "label_de": "Deutsch",
                "label_iso": "German"
            },
            {
                "value": "gle",
                "label_de": "Irisch",
                "label_iso": "Irish"
            },
            {
                "value": "glg",
                "label_de": "Galicisch, Galegisch",
                "label_iso": "Galician"
            },
            {
                "value": "got",
                "label_de": "Gotisch",
                "label_iso": "Gothic"
            },
            {
                "value": "gre",
                "label_de": "Griechisch",
                "label_iso": "Greek, Modern (ab 1453)"
            },
            {
                "value": "heb",
                "label_de": "Hebräisch",
                "label_iso": "Hebrew"
            },
            {
                "value": "hrv",
                "label_de": "Kroatisch",
                "label_iso": "Croatian"
            },
            {
                "value": "hun",
                "label_de": "Ungarisch",
                "label_iso": "Hungarian"
            },
            {
                "value": "ind",
                "label_de": "Indonesisch",
                "label_iso": "Indonesian"
            },
            {
                "value": "ita",
                "label_de": "Italienisch",
                "label_iso": "Italian"
            },
            {
                "value": "jpn",
                "label_de": "Japanisch",
                "label_iso": "Japanese"
            },
            {
                "value": "kor",
                "label_de": "Koreanisch",
                "label_iso": "Korean"
            },
            {
                "value": "lat",
                "label_de": "Latein",
                "label_iso": "Latin"
            },
            {
                "value": "lav",
                "label_de": "Lettisch",
                "label_iso": "Latvian"
            },
            {
                "value": "lit",
                "label_de": "Litauisch",
                "label_iso": "Lithuanian"
            },
            {
                "value": "mac",
                "label_de": "Mazedonisch",
                "label_iso": "Macedonian"
            },
            {
                "value": "mus",
                "label_de": "Muskogee-Sprachen",
                "label_iso": "Creek"
            },
            {
                "value": "nob",
                "label_de": "Bokmål",
                "label_iso": "Bokmål, Norwegian; Norwegian Bokmål"
            },
            {
                "value": "nor",
                "label_de": "Norwegisch",
                "label_iso": "Norwegian"
            },
            {
                "value": "per",
                "label_de": "Persisch",
                "label_iso": "Persian"
            },
            {
                "value": "pol",
                "label_de": "Polnisch",
                "label_iso": "Polish"
            },
            {
                "value": "por",
                "label_de": "Portugiesisch",
                "label_iso": "Portuguese"
            },
            {
                "value": "rum",
                "label_de": "Rumänisch",
                "label_iso": "Romanian; Moldavian; Moldovan"
            },
            {
                "value": "rus",
                "label_de": "Russisch",
                "label_iso": "Russian"
            },
            {
                "value": "sin",
                "label_de": "Singhalesisch",
                "label_iso": "Sinhala; Sinhalese"
            },
            {
                "value": "sla",
                "label_de": "Slawische Sprachen",
                "label_iso": "Slavic languages"
            },
            {
                "value": "slo",
                "label_de": "Slowakisch",
                "label_iso": "Slovak"
            },
            {
                "value": "slv",
                "label_de": "Slowenisch",
                "label_iso": "Slovenian"
            },
            {
                "value": "spa",
                "label_de": "Spanisch, Kastilisch",
                "label_iso": "Spanish; Castilian"
            },
            {
                "value": "srp",
                "label_de": "Serbisch",
                "label_iso": "Serbian"
            },
            {
                "value": "swe",
                "label_de": "Schwedisch",
                "label_iso": "Swedish"
            },
            {
                "value": "tha",
                "label_de": "Tahitianisch, Tahitisch",
                "label_iso": "Tahitian"
            },
            {
                "value": "tur",
                "label_de": "Türkisch",
                "label_iso": "Turkish"
            },
            {
                "value": "ukr",
                "label_de": "Ukrainisch",
                "label_iso": "Ukrainian"
            },
            {
                "value": "vie",
                "label_de": "Vietnamesisch",
                "label_iso": "Vietnamese"
            },
            {
                "value": "yid",
                "label_de": "Jiddisch",
                "label_iso": "Yiddish"
            }
        ],
        wikiCountry : [
            {
                "value": "BAV",
                "label": "Bayern"
            },
            {
                "value": "DEU",
                "label": "Deutschland"
            },
            {
                "value": "FRA",
                "label": "Frankreich"
            },
            {
                "value": "NLD",
                "label": "Holland"
            },
            {
                "value": "ITA",
                "label": "Italien"
            },
            {
                "value": "YUG",
                "label": "Jugoslawien"
            },
            {
                "value": "HRV",
                "label": "Kroatien"
            },
            {
                "value": "AUT",
                "label": "Österreich"
            },
            {
                "value": "POL",
                "label": "Polen"
            },
            {
                "value": "PRU",
                "label": "Preußen"
            },
            {
                "value": "ROU",
                "label": "Rumänien"
            },
            {
                "value": "RUS",
                "label": "Russland"
            },
            {
                "value": "SAX",
                "label": "Sachsen"
            },
            {
                "value": "TJK",
                "label": "Tadschikistan"
            },
            {
                "value": "THU",
                "label": "Thüringen"
            },
            {
                "value": "USA",
                "label": "U. S. A."
            },
            {
                "value": "WUR",
                "label": "Württemberg"
            }
        ]
    };
}