<?php

/*
 * Copyright (c) 2018 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;

/**
 * Create a Fic object
 *
 * @author sylae and skyyrunner
 */
class FicFactory
{

    /**
     * Pull a fic from the database.
     * @param int $id SB topic ID
     * @return \Armbreaker\Fic
     * @throws NotFoundError
     * @throws \LogicException
     */
    public static function getFic(int $id): Fic
    {
        // todo: caching
        $sql = DatabaseFactory::get()->prepare("select * from armbreaker_fics where tid= ?");
        $sql->bindValue(1, $id, 'integer');
        $sql->execute();
        $fic = $sql->fetchAll();
        if (count($fic) == 1) {
            return new Fic($fic[0]['tid'], $fic[0]['title']);
        } elseif (count($fic) == 0) {
            throw new NotFoundError("Fic ID not found :(");
        } else {
            throw new \LogicException("More than one Fic ID matched?");
        }
    }

    /**
     * Create an empty fic instance
     * @param int $id SB topic ID
     * @param string $name Fic name
     * @return \Armbreaker\Fic
     */
    public static function createFic(int $id, string $name): Fic
    {
        $fic = new Fic($id, $name);
        $fic->sync();
        return $fic;
    }

    /**
     * Get all databased fics.
     * @return \Armbreaker\FicCollection
     * @todo where age > time
     */
    public static function getAllFics(): FicCollection
    {
        $sql  = DatabaseFactory::get()->prepare("select * from armbreaker_fics");
        $sql->execute();
        $fics = new FicCollection();
        foreach ($sql->fetchAll() as $fic) {
            $fics->addFic(new Fic($fic['tid'], $fic['title']));
        }
        return $fics;
    }
}
