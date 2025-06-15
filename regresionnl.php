<?php
// Funciones auxiliares para la regresión cuadrática y métricas
function regresion_cuadratica($x, $y) {
    $n = count($x);
    $sum_x = array_sum($x);
    $sum_y = array_sum($y);
    $sum_x2 = 0;
    $sum_x3 = 0;
    $sum_x4 = 0;
    $sum_xy = 0;
    $sum_x2y = 0;

    for ($i = 0; $i < $n; $i++) {
        $x_i = $x[$i];
        $y_i = $y[$i];
        $sum_x2 += $x_i * $x_i;
        $sum_x3 += $x_i * $x_i * $x_i;
        $sum_x4 += $x_i * $x_i * $x_i * $x_i;
        $sum_xy += $x_i * $y_i;
        $sum_x2y += $x_i * $x_i * $y_i;
    }

    // Matriz para las ecuaciones normales:
    $A = [
        [$n, $sum_x, $sum_x2],
        [$sum_x, $sum_x2, $sum_x3],
        [$sum_x2, $sum_x3, $sum_x4]
    ];

    $B = [$sum_y, $sum_xy, $sum_x2y];

    // Resolver el sistema lineal A * coeficientes = B
    $coeficientes = resolver_sistema_lineal($A, $B);
    return $coeficientes;
}

function resolver_sistema_lineal($A, $B) {
    $detA = determinante3x3($A);
    if (abs($detA) < 1e-12) return null;

    $A1 = [[$B[0], $A[0][1], $A[0][2]],
           [$B[1], $A[1][1], $A[1][2]],
           [$B[2], $A[2][1], $A[2][2]]];
    $A2 = [[$A[0][0], $B[0], $A[0][2]],
           [$A[1][0], $B[1], $A[1][2]],
           [$A[2][0], $B[2], $A[2][2]]];
    $A3 = [[$A[0][0], $A[0][1], $B[0]],
           [$A[1][0], $A[1][1], $B[1]],
           [$A[2][0], $A[2][1], $B[2]]];

    $x = determinante3x3($A1) / $detA;
    $y = determinante3x3($A2) / $detA;
    $z = determinante3x3($A3) / $detA;

    return [$x, $y, $z];
}

function determinante3x3($m) {
    return $m[0][0]*($m[1][1]*$m[2][2]-$m[1][2]*$m[2][1])
         - $m[0][1]*($m[1][0]*$m[2][2]-$m[1][2]*$m[2][0])
         + $m[0][2]*($m[1][0]*$m[2][1]-$m[1][1]*$m[2][0]);
}

function predecir_cuadratica($coeficientes, $x) {
    list($c, $b, $a) = $coeficientes;
    return $a * $x * $x + $b * $x + $c;
}

function error_cuadratico_medio($y_real, $y_pred) {
    $n = count($y_real);
    $sum = 0;
    for ($i = 0; $i < $n; $i++)
        $sum += ($y_real[$i] - $y_pred[$i]) ** 2;
    return $sum / $n;
}

function error_medio_absoluto($y_real, $y_pred) {
    $n = count($y_real);
    $sum = 0;
    for ($i = 0; $i < $n; $i++)
        $sum += abs($y_real[$i] - $y_pred[$i]);
    return $sum / $n;
}

function coeficiente_determinacion($y_real, $y_pred) {
    $ss_total = array_sum(array_map(function($y) use ($y_real) {
        return ($y - array_sum($y_real) / count($y_real)) ** 2;
    }, $y_real));
    $ss_residual = array_sum(array_map(function($y_r, $y_p) {
        return ($y_r - $y_p) ** 2;
    }, $y_real, $y_pred));
    return 1 - ($ss_residual / $ss_total);
}

function error_estandar_regresion($y_real, $y_pred) {
    return sqrt(error_cuadratico_medio($y_real, $y_pred));
}

// Procesar datos al enviar el formulario
$resultados = [];
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $datos = explode("\n", trim($_POST["datos"]));
    $x = [];
    $y = [];
    foreach ($datos as $dato) {
        list($xi, $yi) = explode(",", trim($dato));
        $x[] = floatval($xi);
        $y[] = floatval($yi);
    }

    $coeficientes = regresion_cuadratica($x, $y);
    $y_pred = array_map(function($xi) use ($coeficientes) {
        return predecir_cuadratica($coeficientes, $xi);
    }, $x);

    $mse = error_cuadratico_medio($y, $y_pred);
    $mae = error_medio_absoluto($y, $y_pred);
    $r2 = coeficiente_determinacion($y, $y_pred);
    $error_estandar = error_estandar_regresion($y, $y_pred);

    $resultados = [
        'coeficientes' => $coeficientes,
        'mse' => $mse,
        'mae' => $mae,
        'r2' => $r2,
        'error_estandar' => $error_estandar,
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regresión No Lineal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        h1 {
            text-align: center;
        }
        .container {
            max-width: 800px;
            margin: auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        textarea {
            width: 100%;
            height: 150px;
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .result {
            margin-top: 20px;
        }
        canvas {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Regresión No Lineal</h1>
        <form method="post">
            <label for="datos">Ingrese los datos pareados (x,y) separados por comas y cada par en una nueva línea:</label>
            <textarea name="datos" id="datos" required></textarea>
            <button type="submit">Calcular Regresión</button>
        </form>

        <?php if (!empty($resultados)): ?>
            <div class="result">
                <h2>Resultados</h2>
                <p><strong>Ecuación de Regresión:</strong> y = <?= round($resultados['coeficientes'][2], 4) ?>x² + <?= round($resultados['coeficientes'][1], 4) ?>x + <?= round($resultados['coeficientes'][0], 4) ?></p>
                <p><strong>Error Cuadrático Medio (MSE):</strong> <?= round($resultados['mse'], 4) ?></p>
                <p><strong>Error Medio Absoluto (MAE):</strong> <?= round($resultados['mae'], 4) ?></p>
                <p><strong>Coeficiente de Determinación (R²):</strong> <?= round($resultados['r2'], 4) ?></p>
                <p><strong>Error Estándar de la Regresión:</strong> <?= round($resultados['error_estandar'], 4) ?></p>
            </div>
            <canvas id="grafico"></canvas>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('grafico').getContext('2d');
        const x = <?= json_encode($x) ?>;
        const y = <?= json_encode($y) ?>;
        const coeficientes = <?= json_encode($resultados['coeficientes']) ?>;

        const y_pred = x.map(xi => coeficientes[2] * xi * xi + coeficientes[1] * xi + coeficientes[0]);

        new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: 'Datos Reales',
                        data: x.map((xi, index) => ({ x: xi, y: y[index] })),
                        backgroundColor: 'rgba(75, 192, 192, 1)',
                    },
                    {
                        label: 'Predicción de Regresión',
                        data: x.map((xi, index) => ({ x: xi, y: y_pred[index] })),
                        borderColor: 'rgba(255, 99, 132, 1)',
                        fill: false,
                        type: 'line',
                    }
                ]
            },
            options: {
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'X'
                        }
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Y'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>