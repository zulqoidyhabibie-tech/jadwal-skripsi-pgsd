<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Baca berkas impor (Excel .xlsx atau CSV) dan buat template Excel. */
class Tabel
{
    public const ATURAN_BERKAS = ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'];

    /** @return array<int, array<string, string>> baris asosiatif berdasarkan baris judul */
    public static function baca(UploadedFile $file): array
    {
        $ext = strtolower($file->getClientOriginalExtension());

        return $ext === 'xlsx' ? self::bacaExcel($file->getRealPath()) : Csv::baca($file->getRealPath());
    }

    public static function kunci(string $judul): string
    {
        return strtolower(preg_replace('/\s+/', '_', trim($judul)));
    }

    private static function bacaExcel(string $path): array
    {
        $reader = new Reader;
        $reader->open($path);
        $judul = null;
        $hasil = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $nilai = array_map(fn ($v) => self::teks($v), $row->toArray());
                if (implode('', $nilai) === '') {
                    continue;
                }
                if ($judul === null) {
                    $judul = array_map(fn ($h) => self::kunci($h), $nilai);

                    continue;
                }
                $nilai = array_pad(array_slice($nilai, 0, count($judul)), count($judul), '');
                $hasil[] = array_combine($judul, $nilai);
            }
            break; // hanya sheet pertama
        }
        $reader->close();

        return $hasil;
    }

    private static function teks(mixed $v): string
    {
        return match (true) {
            $v instanceof DateTimeInterface => $v->format('Y-m-d'),
            is_float($v) && floor($v) === $v && abs($v) < 1e15 => (string) (int) $v,
            is_bool($v) => $v ? '1' : '0',
            default => trim((string) $v),
        };
    }

    /**
     * Buat template .xlsx: sheet pertama berisi judul kolom dan contoh, sheet kedua berisi petunjuk.
     *
     * @param  array<string, string>  $kolom  nama kolom => penjelasan
     * @param  array<int, array<int, string|int>>  $contoh
     */
    public static function template(string $namaBerkas, array $kolom, array $contoh): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        $writer = new Writer;
        $writer->openToFile($path);

        $judul = (new Style)->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('1F6B52');
        $teks = (new Style)->setFormat('@');
        $sheet = $writer->getCurrentSheet();
        $sheet->setName('Data');
        $sheet->setColumnWidthForRange(22, 1, count($kolom));
        $writer->addRow(Row::fromValues(array_keys($kolom), $judul));
        foreach ($contoh as $baris) {
            $writer->addRow(Row::fromValues(array_map('strval', $baris), $teks));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Petunjuk');
        $writer->getCurrentSheet()->setColumnWidth(24, 1);
        $writer->getCurrentSheet()->setColumnWidth(90, 2);
        $writer->addRow(Row::fromValues(['Kolom', 'Penjelasan'], $judul));
        foreach ($kolom as $k => $ket) {
            $writer->addRow(Row::fromValues([$k, $ket]));
        }
        $writer->addRow(Row::fromValues(['']));
        $writer->addRow(Row::fromValues(['Catatan', 'Isi data di sheet "Data" mulai baris 2. Hapus baris contoh sebelum mengimpor. Jangan ubah judul kolom di baris 1.']));
        $writer->close();

        return response()->download($path, $namaBerkas, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
