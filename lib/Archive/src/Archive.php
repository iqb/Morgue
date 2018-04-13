<?php

/*
 * (c) 2018 Dennis Birkholz <dennis@birkholz.org>
 *
 * $Id$
 * Author:    $Format:%an <%ae>, %ai$
 * Committer: $Format:%cn <%ce>, %ci$
 */

namespace morgue\archive;

/**
 * @author Dennis Birkholz <dennis@birkholz.org>
 */
final class Archive
{
    /**
     * @var string
     */
    private $comment;

    /**
     * @var ArchiveEntry[]
     */
    private $entries = [];

    /**
     * @var int[]
     */
    private $entriesByName = [];

    /**
     * @return string|null
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     * @return Archive
     */
    public function withComment(string $comment = null): Archive
    {
        $obj = clone $this;
        $obj->comment = $comment;
        return $obj;
    }

    /**
     * @return ArchiveEntry[]
     */
    public function getEntries(): array
    {
        return $this->entries;
    }

    /**
     * @param $idOrName
     * @return ArchiveEntry|null
     */
    public function getEntry($idOrName)
    {
        if (\is_int($idOrName)) {
            return (isset($this->entries[$idOrName]) ? $this->entries[$idOrName] : null);
        }

        elseif (\is_string($idOrName)) {
            return (isset($this->entriesByName[$idOrName]) ? $this->entries[$this->entriesByName[$idOrName]] : null);
        }

        else {
            throw new \InvalidArgumentException("Can only search by entry id or name.");
        }
    }

    /**
     * Add an entry to the archive.
     *
     * @param ArchiveEntry $entry
     * @param int|null $index
     * @return Archive
     */
    public function addEntry(ArchiveEntry $entry, int $index = null): Archive
    {
        if (\in_array($entry, $this->entries, true)) {
            throw new \InvalidArgumentException("The supplied entry exists already in this Archive.");
        }

        if ($index !== null && isset($this->entries[$index])) {
            throw new \InvalidArgumentException("Can not replace existing index #$index in addEntry().");
        }

        if (isset($this->entriesByName[$entry->getName()])) {
            throw new \InvalidArgumentException("Can not add entry with already existing name '" . $entry->getName() . "'.");
        }

        $obj = clone $this;
        if ($index !== null) {
            $obj->entries[$index] = $entry;
        } else {
            $obj->entries[] = $entry;
            \end($obj->entries);
            $index = \key($obj->entries);
        }
        $obj->entriesByName[$entry->getName()] = $index;
        return $obj;
    }

    /**
     * @param ArchiveEntry $oldEntry
     * @param ArchiveEntry $newEntry
     * @return Archive
     */
    public function replaceEntry(ArchiveEntry $oldEntry, ArchiveEntry $newEntry): Archive
    {
        if (!isset($this->entriesByName[$oldEntry->getName()])) {
            throw new \InvalidArgumentException("Can not remove entry, not found!");
        }

        $oldIndex = $this->entriesByName[$oldEntry->getName()];
        return $this->delEntry($oldIndex)->addEntry($newEntry, $oldIndex);
    }

    /**
     * Remove the supplied entry from the archive.
     *
     * @param ArchiveEntry|int|string $entryOrIdOrName The entry, the entry ID or the entry name
     * @return Archive
     */
    public function delEntry($entryOrIdOrName): Archive
    {
        if (\is_string($entryOrIdOrName)) {
            if (!isset($this->entriesByName[$entryOrIdOrName])) {
                throw new \InvalidArgumentException("No entry with name '$entryOrIdOrName' found in this Archive.");
            } else {
                $index = $this->entriesByName[$entryOrIdOrName];
            }
        }

        elseif ($entryOrIdOrName instanceof ArchiveEntry) {
            if (($index = \array_search($entryOrIdOrName, $this->entries, true)) === false) {
                throw new \InvalidArgumentException("The supplied entry is not found in this Archive.");
            }
        }

        elseif (\is_int($entryOrIdOrName)) {
            if (!isset($this->entries[$entryOrIdOrName])) {
                throw new \InvalidArgumentException("No entry with id #$entryOrIdOrName is found in this Archive.");
            } else {
                $index = (int)$entryOrIdOrName;
            }
        }

        else {
            throw new \InvalidArgumentException("Invalid argument, can only delete ArchiveEntry instance, by index or by name.");
        }

        $entry = $this->entries[$index];

        $obj = clone $this;
        unset($obj->entriesByName[$entry->getName()]);
        unset($obj->entries[$index]);
        return $obj;
    }
}
