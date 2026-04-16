<?php

namespace App\Services;

use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Reader;

class SkillsSpreadsheetParser
{
    private const LEGEND_ENTRIES = [
        'Competence Level',
        'No Knowledge',
        'Awareness',
        'Working',
        'Practitioner',
        'Expert',
        'Score',
    ];

    private const LEVEL_MAP = [
        1 => 'awareness',
        2 => 'working',
        3 => 'practitioner',
        4 => 'expert',
    ];

    public function parse(string $filePath): array
    {
        $reader = new Reader;
        $reader->open($filePath);

        $skills = [];
        $masterResult = ['staffSkills' => [], 'skippedStaff' => []];

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() === 'Baseline') {
                $skills = $this->parseBaselineSheet($sheet);
            }

            if ($sheet->getName() === 'Master') {
                $masterResult = $this->parseMasterSheet($sheet);
            }
        }

        $reader->close();

        return [
            'skills' => $skills,
            'staffSkills' => $masterResult['staffSkills'],
            'skippedStaff' => $masterResult['skippedStaff'],
        ];
    }

    private function parseBaselineSheet($sheet): array
    {
        $skills = [];
        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex++;

            if ($rowIndex <= 3) {
                continue;
            }

            $cells = $row->getCells();
            $code = trim((string) $this->getCellValue($cells[0] ?? null));
            $name = trim((string) $this->getCellValue($cells[1] ?? null));
            $description = trim((string) $this->getCellValue($cells[2] ?? null));

            if ($name === '' || in_array($name, self::LEGEND_ENTRIES)) {
                continue;
            }

            $category = $code !== '' ? $this->mapCodeToCategory($code) : 'General';

            // Deduplicate by name — prefer entries with a code over those without
            $existingIndex = array_search($name, array_column($skills, 'name'));
            if ($existingIndex !== false) {
                if ($code !== '' && $skills[$existingIndex]['code'] === '') {
                    $skills[$existingIndex] = compact('code', 'name', 'description', 'category');
                }

                continue;
            }

            $skills[] = compact('code', 'name', 'description', 'category');
        }

        return $skills;
    }

    private function parseMasterSheet($sheet): array
    {
        $staffSkills = [];
        $allStaffNames = [];
        $rowIndex = 0;

        foreach ($sheet->getRowIterator() as $row) {
            $rowIndex++;

            if ($rowIndex === 1) {
                continue;
            }

            $cells = $row->getCells();
            $name = trim((string) $this->getCellValue($cells[0] ?? null));
            $competency = trim((string) $this->getCellValue($cells[2] ?? null));
            $actual = $this->getCellValue($cells[6] ?? null);

            if ($name === '') {
                continue;
            }

            $allStaffNames[$name] = true;

            if ($competency === '') {
                continue;
            }

            $actualInt = (int) $actual;

            if ($actualInt === 0 || $actualInt === 99 || ! isset(self::LEVEL_MAP[$actualInt])) {
                continue;
            }

            $staffSkills[$name][$competency] = self::LEVEL_MAP[$actualInt];
        }

        $skippedStaff = array_values(array_diff(
            array_keys($allStaffNames),
            array_keys($staffSkills)
        ));

        return [
            'staffSkills' => $staffSkills,
            'skippedStaff' => $skippedStaff,
        ];
    }

    private function getCellValue($cell): mixed
    {
        if ($cell === null) {
            return '';
        }

        if ($cell instanceof FormulaCell) {
            return $cell->getComputedValue();
        }

        return $cell->getValue();
    }

    private function mapCodeToCategory(string $code): string
    {
        return match (true) {
            str_starts_with($code, 'CoSECore') => 'Core',
            str_starts_with($code, 'CoSESvc') => 'Service',
            str_starts_with($code, 'CoSESec') => 'Security',
            str_starts_with($code, 'CoseMgt'), str_starts_with($code, 'CoSEMgt') => 'Management',
            str_starts_with($code, 'CoSEGov') => 'Governance',
            str_starts_with($code, 'CoSETec') => 'Technical',
            str_starts_with($code, 'CoSEBus') => 'Business',
            default => 'General',
        };
    }
}
