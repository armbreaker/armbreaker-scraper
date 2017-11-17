<?php

/*
 * The MIT License
 *
 * Copyright 2017 sylae and skyyrunner.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Armbreaker;

/**
 * Create a Fic object
 *
 * @author sylae and skyyrunner
 */
class FicFactory {

  public static function getFic(int $id): Fic {
    // todo: caching
    $sql = DatabaseFactory::get()->prepare("select * from armbreaker_fics where tid= ?");
    $sql->bindValue(1, $id, 'integer');
    $sql->execute();
    $fic = $sql->fetchAll();
    if (count($fic) == 1) {
      return new Fic($fic[0]['tid'], $fic[0]['title']);
    } elseif (count($fic) == 0) {
      throw new \Exception("Fic ID not found :(");
    } else {
      throw new \LogicException("More than one Fic ID matched?");
    }
  }

  public static function createFic(int $id, string $name): Fic {
    $fic = new Fic($id, $name);
    $fic->sync();
    return $fic;
  }

}
