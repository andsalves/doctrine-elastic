<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace DoctrineElastic\Elastic;

/**
 *
 * Container for all ORM events of DoctrineElastic that aren't in Events class of Doctrine
 *
 * @author Andsalves <ands.alves.nunes@gmail.com>
 */
final class DoctrineElasticEvents {

    /**
     * Private constructor. This class is not meant to be instantiated.
     */
    private function __construct() {
    }

    const beforeInsert = 'beforeInsert';
    const postInsert = 'postInsert';

    const beforeUpdate = 'beforeUpdate';
    const postUpdate = 'postUpdate';

    const beforeDelete = 'beforeDelete';
    const postDelete = 'postDelete';

    const beforeQuery = 'beforeQuery';
    const postQuery = 'postQuery';
}
