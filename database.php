<?php
/**
 * database.php
 *
 * @author Nicolas CARPi <nicolas.carpi@curie.fr>
 * @copyright 2012 Nicolas CARPi
 * @see http://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use \Exception;

/**
 * Entry point for database things
 *
 */
require_once 'inc/common.php';
$page_title = _('Database');
$selected_menu = 'Database';
require_once 'inc/head.php';

// add the chemdoodle stuff if we want it
echo addChemdoodle();

try {

    if (!isset($_GET['mode']) || empty($_GET['mode']) || $_GET['mode'] === 'show') {
        $databaseView = new DatabaseView(new Database($_SESSION['team_id']));
        $databaseView->display = $_SESSION['prefs']['display'];

        // CATEGORY FILTER
        if (isset($_GET['filter']) && !empty($_GET['filter']) && Tools::checkId($_GET['filter'])) {
            $databaseView->Database->categoryFilter = "AND items_types.id = " . $_GET['filter'];
            $databaseView->searchType = 'filter';
        }
        // TAG FILTER
        if (isset($_GET['tag']) && $_GET['tag'] != '') {
            $tag = filter_var($_GET['tag'], FILTER_SANITIZE_STRING);
            $databaseView->tag = $tag;
            $databaseView->Database->tagFilter = "AND items_tags.tag LIKE '" . $tag . "'";
            $databaseView->searchType = 'tag';
        }
        // QUERY FILTER
        if (isset($_GET['q']) && !empty($_GET['q'])) {
            $query = filter_var($_GET['q'], FILTER_SANITIZE_STRING);
            $databaseView->query = $query;
            $databaseView->Database->queryFilter = "AND (title LIKE '%$query%' OR date LIKE '%$query%' OR body LIKE '%$query%')";
            $databaseView->searchType = 'query';
        }
        // ORDER
        if (isset($_GET['order'])) {
            if ($_GET['order'] === 'cat') {
                $databaseView->Database->order = 'items_types.name';
            } elseif ($_GET['order'] === 'date' || $_GET['order'] === 'rating' || $_GET['order'] === 'title') {
                $databaseView->Database->order = 'items.' . $_GET['order'];
            }
        }
        // SORT
        if (isset($_GET['sort'])) {
            if ($_GET['sort'] === 'asc' || $_GET['sort'] === 'desc') {
                $databaseView->Database->sort = $_GET['sort'];
            }
        }

        echo $databaseView->buildShowMenu('database');
        echo $databaseView->buildShow();

    // VIEW
    } elseif ($_GET['mode'] === 'view') {

        $databaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $databaseView->view();

    // EDIT
    } elseif ($_GET['mode'] === 'edit') {

        $databaseView = new DatabaseView(new Database($_SESSION['team_id'], $_GET['id']));
        echo $databaseView->edit();
    }
} catch (Exception $e) {
    display_message('ko', $e->getMessage());
} finally {
    require_once 'inc/footer.php';
}
