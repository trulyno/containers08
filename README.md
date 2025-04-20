# Darea de seamă
**Lucrarea de Laborator 8: Integrare continuă cu Github Actions**

---

### Scopul lucrării
În cadrul acestei lucrări studenții vor învăța să configureze integrarea continuă cu ajutorul Github Actions.

### Sarcina
Crearea unei aplicații Web, scrierea testelor pentru aceasta și configurarea integrării continue cu ajutorul Github Actions pe baza containerelor.

### Descrierea executării lucrării

#### 1. Crearea repozitoriului și structurii inițiale
Am creat direcotiul containers08, iar în el am creat structura necesară pentru aplicația web PHP:

```
containers08/
├── site/
│   ├── modules/
│   │   ├── database.php
│   │   └── page.php
│   ├── templates/
│   │   └── index.tpl
│   ├── styles/
│   │   └── style.css
│   ├── config.php
│   └── index.php
├── tests/
└── Dockerfile
```

#### 2. Crearea aplicației Web PHP

##### Fișierul modules/database.php
Am creat clasa `Database` pentru lucrul cu baza de date SQLite, implementând toate metodele cerute:

```php
<?php

class Database {
    private $db;

    public function __construct($path) {
        $this->db = new PDO('sqlite:' . $path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function Execute($sql) {
        return $this->db->exec($sql);
    }

    public function Fetch($sql) {
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Create($table, $data) {
        $fields = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        return $this->db->lastInsertId();
    }

    public function Read($table, $id) {
        $sql = "SELECT * FROM {$table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function Update($table, $id, $data) {
        $updates = [];
        foreach (array_keys($data) as $field) {
            $updates[] = "{$field} = :{$field}";
        }
        $updatesStr = implode(', ', $updates);
        
        $sql = "UPDATE {$table} SET {$updatesStr} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':id', $id);
        
        return $stmt->execute();
    }

    public function Delete($table, $id) {
        $sql = "DELETE FROM {$table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id);
        return $stmt->execute();
    }

    public function Count($table) {
        $sql = "SELECT COUNT(*) as count FROM {$table}";
        $stmt = $this->db->query($sql);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
}

?>
```

##### Fișierul modules/page.php
Am implementat clasa `Page` pentru randarea paginilor web:

```php
<?php

class Page {
    private $template;

    public function __construct($template) {
        $this->template = file_get_contents($template);
    }

    public function Render($data) {
        $result = $this->template;
        
        foreach ($data as $key => $value) {
            $result = str_replace('{{' . $key . '}}', $value, $result);
        }
        
        return $result;
    }
}

?>
```

##### Fișierul templates/index.tpl
Am creat un șablon simplu pentru paginile web:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{title}}</title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <h1>{{title}}</h1>
        <div class="content">{{content}}</div>
    </div>
</body>
</html>
```

##### Fișierul styles/style.css
Am adăugat stiluri CSS de bază:

```css
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
}

.container {
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 5px;
}

h1 {
    color: #333;
}

.content {
    line-height: 1.6;
}
```

##### Fișierul index.php
Am implementat codul pentru afișarea paginii:

```php
<?php

require_once __DIR__ . '/modules/database.php';
require_once __DIR__ . '/modules/page.php';

require_once __DIR__ . '/config.php';

$db = new Database($config["db"]["path"]);

$page = new Page(__DIR__ . '/templates/index.tpl');

// Get page ID from GET parameter with validation
$pageId = isset($_GET['page']) ? intval($_GET['page']) : 1;

$data = $db->Read("page", $pageId);

// If page not found, use default data
if (!$data) {
    $data = [
        'title' => 'Page not found',
        'content' => 'The requested page does not exist.'
    ];
}

echo $page->Render($data);

?>
```

##### Fișierul config.php
Am creat configurări pentru aplicație:

```php
<?php

$config = [
    "db" => [
        "path" => "/var/www/db/db.sqlite"
    ]
];

?>
```

#### 3. Pregătirea fișierului SQL pentru baza de date

Am creat directorul `sql` în folderul `site` și am adăugat fișierul `schema.sql`:

```sql
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

#### 4. Crearea testelor

Am implementat testele unitare pentru aplicație:

##### Fișierul tests/testframework.php
Am folosit framework-ul de testare furnizat în sarcină.

```php
<?php

function message($type, $message) {
    $time = date('Y-m-d H:i:s');
    echo "{$time} [{$type}] {$message}" . PHP_EOL;
}

function info($message) {
    message('INFO', $message);
}

function error($message) {
    message('ERROR', $message);
}

function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
    if ($expression) {
        info($pass);
        return true;
    }
    error($fail);
    return false;
}

class TestFramework {
    private $tests = [];
    private $success = 0;

    public function add($name, $test) {
        $this->tests[$name] = $test;
    }

    public function run() {
        foreach ($this->tests as $name => $test) {
            info("Running test {$name}");
            if ($test()) {
                $this->success++;
            }
            info("End test {$name}");
        }
    }

    public function getResult() {
        return "{$this->success} / " . count($this->tests);
    }
}

?>
```

##### Fișierul tests/tests.php
Am implementat teste pentru toate metodele clasei Database și Page:

```php
<?php

require_once __DIR__ . '/testframework.php';

// Adaptăm calea pentru a funcționa în containerul Docker
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../modules/database.php';
require_once __DIR__ . '/../modules/page.php';

$tests = new TestFramework();

// test 1: check database connection
function testDbConnection() {
    global $config;
    
    try {
        $db = new Database($config["db"]["path"]);
        return assertExpression(true, "Database connection successful");
    } catch (Exception $e) {
        return assertExpression(false, "Should connect to database", "Database connection failed: " . $e->getMessage());
    }
}

// test 2: test count method
function testDbCount() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $count = $db->Count("page");
    
    return assertExpression($count >= 3, "Count returned correct number of records: " . $count, "Count should return at least 3 records, got: " . $count);
}

// test 3: test create method
function testDbCreate() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $initialCount = $db->Count("page");
    
    $data = [
        'title' => 'Test Page',
        'content' => 'Test Content'
    ];
    
    $id = $db->Create("page", $data);
    $newCount = $db->Count("page");
    
    $success = $id > 0 && $newCount == $initialCount + 1;
    return assertExpression($success, "Create method works correctly, new ID: " . $id, "Create method failed");
}

// test 4: test read method
function testDbRead() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $data = $db->Read("page", 1);
    
    $success = is_array($data) && isset($data['id']) && $data['id'] == 1;
    return assertExpression($success, "Read method works correctly", "Read method failed");
}

// test 5: test update method
function testDbUpdate() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    
    // Create a test record
    $data = [
        'title' => 'Update Test',
        'content' => 'Before Update'
    ];
    
    $id = $db->Create("page", $data);
    
    // Update the record
    $updateData = [
        'content' => 'After Update'
    ];
    
    $result = $db->Update("page", $id, $updateData);
    
    // Verify update
    $updated = $db->Read("page", $id);
    
    $success = $result && $updated['content'] == 'After Update';
    return assertExpression($success, "Update method works correctly", "Update method failed");
}

// test 6: test delete method
function testDbDelete() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    
    // Create a test record to delete
    $data = [
        'title' => 'Delete Test',
        'content' => 'To be deleted'
    ];
    
    $id = $db->Create("page", $data);
    $initialCount = $db->Count("page");
    
    // Delete the record
    $result = $db->Delete("page", $id);
    $newCount = $db->Count("page");
    
    $success = $result && $newCount == $initialCount - 1;
    return assertExpression($success, "Delete method works correctly", "Delete method failed");
}

// test 7: test fetch method
function testDbFetch() {
    global $config;
    
    $db = new Database($config["db"]["path"]);
    $results = $db->Fetch("SELECT * FROM page LIMIT 3");
    
    $success = is_array($results) && count($results) > 0;
    return assertExpression($success, "Fetch method works correctly, returned " . count($results) . " records", "Fetch method failed");
}

// test 8: test page render
function testPageRender() {
    $page = new Page(__DIR__ . '/../templates/index.tpl');
    
    $data = [
        'title' => 'Test Title',
        'content' => 'Test Content'
    ];
    
    $result = $page->Render($data);
    
    $success = strpos($result, 'Test Title') !== false && strpos($result, 'Test Content') !== false;
    return assertExpression($success, "Page render works correctly", "Page render failed");
}

// add tests
$tests->add('Database connection', 'testDbConnection');
$tests->add('Table count', 'testDbCount');
$tests->add('Data create', 'testDbCreate');
$tests->add('Data read', 'testDbRead');
$tests->add('Data update', 'testDbUpdate');
$tests->add('Data delete', 'testDbDelete');
$tests->add('Data fetch', 'testDbFetch');
$tests->add('Page render', 'testPageRender');

// run tests
$tests->run();

echo "Tests result: " . $tests->getResult();
```

#### 5. Crearea Dockerfile

Am creat fișierul Dockerfile în directorul rădăcină:

```Dockerfile
FROM php:7.4-fpm as base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY site/sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html
```

#### 6. Configurarea Github Actions

Am creat directorul `.github/workflows` și am adăugat fișierul `main.yml`:

```yaml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy tests to the container
        run: docker cp ./tests container:/var/www/html
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

#### 7. Testare și verificare

Am făcut push la toate modificările în repositoriu și am verificat execuția workflow-ului în fila Actions din GitHub. Testele au fost executate cu succes în containerul Docker.

### Răspunsuri la întrebări:

#### 1. Ce este integrarea continuă?
Integrarea continuă (Continuous Integration - CI) este o practică de dezvoltare software prin care dezvoltatorii încarcă frecvent codul în repositoriul partajat (de obicei de mai multe ori pe zi). Fiecare încărcare declanșează automat o serie de teste pentru a verifica dacă noul cod nu afectează funcționalitatea existentă. Acest proces ajută la identificarea și rezolvarea rapidă a problemelor, reducând astfel timpul necesar pentru integrarea modificărilor și îmbunătățind calitatea codului. Integrarea continuă este adesea prima etapă în implementarea unui pipeline complet de CI/CD (Continuous Integration/Continuous Deployment).

#### 2. Pentru ce sunt necesare testele unitare? Cât de des trebuie să fie executate?
Testele unitare sunt necesare pentru:
- Verificarea că fiecare unitate de cod (funcție, metodă, clasă) funcționează corect conform specificațiilor
- Depistarea timpurie a erorilor și bug-urilor
- Facilitarea refactorizării codului prin asigurarea că modificările nu introduc noi probleme
- Servirea drept documentație pentru comportamentul așteptat al codului
- Îmbunătățirea calității și design-ului codului

Testele unitare ar trebui executate:
- La fiecare commit sau push în repository
- Înainte de orice integrare a codului în ramura principală
- Pe mașina dezvoltatorului înainte de a încărca schimbările (ideal)
- În cadrul pipeline-urilor CI pentru fiecare modificare
- Înainte de lansarea produsului

În practica modernă de CI/CD, testele unitare sunt executate automat de mai multe ori pe zi, la fiecare modificare a codului.

#### 3. Care modificări trebuie făcute în fișierul .github/workflows/main.yml pentru a rula testele la fiecare solicitare de trage (Pull Request)?
Trebuie modificată secțiunea `on` pentru a include și evenimentele de tip Pull Request:

```yaml
on:
  push:
    branches:
      - main
  pull_request:
    branches:
      - main
```

Această configurație va declanșa workflow-ul atât la push-uri pe ramura main, cât și la crearea sau actualizarea pull request-urilor către main.

#### 4. Ce trebuie adăugat în fișierul .github/workflows/main.yml pentru a șterge imaginile create după testare?
Pentru a șterge imaginile Docker create după testare, trebuie adăugat un pas suplimentar la sfârșitul workflow-ului:

```yaml
      - name: Clean up Docker images
        run: docker rmi containers08 --force
```

Acest pas va șterge imaginea Docker creată în timpul procesului de testare, eliberând astfel spațiul de stocare. Flag-ul `--force` asigură ștergerea imaginii chiar dacă există containere care o folosesc.

### Concluzii

Acest laborator a fost foarte util pentru înțelegerea integrării continue folosind GitHub Actions. Am reușit să creez o aplicație web PHP simplă și am configurat un workflow de CI bazat pe containere Docker care execută automat testele la fiecare push în repository. Am învățat:

1. Cum să implementez o aplicație web PHP cu funcționalități de bază pentru lucrul cu baze de date și randarea paginilor
2. Cum să scriu teste unitare pentru a valida funcționalitatea aplicației
3. Cum să creez un Dockerfile pentru containerizarea aplicației
4. Cum să configurez GitHub Actions pentru a automatiza testarea codului

Integrarea continuă oferă numeroase avantaje: identificarea timpurie a problemelor, feedback rapid, îmbunătățirea calității codului și automatizarea proceselor repetitive. Folosirea containerelor Docker în pipeline-urile CI adaugă un nivel suplimentar de izolare și portabilitate, asigurând că testele rulează în medii consistente și reproducibile.

În mediul de producție, această abordare ar putea fi extinsă pentru a include și deployment continuu (CD), astfel încât codul testat și validat să fie automat implementat în mediul de producție după ce trece toate testele.