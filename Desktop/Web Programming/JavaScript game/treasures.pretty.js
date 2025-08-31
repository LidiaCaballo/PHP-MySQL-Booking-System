// Global variable GRID_SIZE is a constant because it will not change along the game.
// This defines the size of the grid (5x5).
const GRID_SIZE = 5;

// grid is an empty array and it will be initialised with zeros at the start of the game.
// This grid is where the game will take place.
let grid = [];
for (let i = 0; i < GRID_SIZE; i++) {
    grid.push([]);
    for (let j = 0; j < GRID_SIZE; j++) {
        grid[i].push(0); // Initialise each cell with 0 (empty).
    }
}

// Start the game with 0 treasures.
// treasuresLeft keeps track of the number of treasures of each value (5, 6, 7, 8) remaining on the grid.
let treasuresLeft = { 5: 0, 6: 0, 7: 0, 8: 0 };

// score stores the total points collected by the player.
let score = 0;

// roundsCompleted keeps track of the number of rounds played.
let roundsCompleted = 0;

// Stores the position of the treasure hunter, -1 meaning not placed yet.
let hunterX = -1, hunterY = -1;

// gameStage tracks the current stage of the game: setup, play, or end.
let gameStage = "setup";

// Helper function to check if a cell is inside the grid by ensuring that x and y are within the grid limit.
function isValidCell(x, y) {
    return x >= 0 && x < GRID_SIZE && y >= 0 && y < GRID_SIZE;
}

// Function to place objects (treasures, obstacles, and hunter) on the board during the setup stage.
// First, check if the cell is already occupied. If it is, show an error message.
// Then, check if the value entered is a number from 5 to 8 and place the treasure.
// If the user enters 'o', place an obstacle. If the user enters 'h', place the hunter.
// If the input is invalid, show an error message.
function placeObject(x, y, value) {
    if (grid[x][y] !== 0) {
        alert("Cell already occupied. You cannot change the object.");
        return;
    }

    if (value === "h" && hunterX !== -1) {
        alert("Only one treasure hunter can be placed.");
        return;
    }

    if (typeof value === "number" && value >= 5 && value <= 8) {
        grid[x][y] = value; // Place a treasure.
        treasuresLeft[value]++; // Increment the count of this treasure type.
    } else if (value === "o") {
        grid[x][y] = "o"; // Place an obstacle.
    } else if (value === "h") {
        grid[x][y] = "h"; // Place the hunter.
        hunterX = x; // Update the hunter's position.
        hunterY = y;
    } else {
        alert("Invalid input! Enter a number between 5 and 8, 'o' for obstacle, or 'h' for hunter.");
        return;
    }

    updateStatus(); // Update the status display.
    renderGrid(); // Re-render the grid to reflect the changes.
}

// Function to render the grid in the HTML page.
// This function dynamically creates and displays the grid based on the current state of the game.
// It also handles user interactions during the setup stage.
/*
* The code for the loading the images has been obtained from:
* ChatGPT: no image is correctly displaying, instead the hunter text gets displayed with the sheet icon.
* OpenAI, ChatGPT 13 March 2025 Version.
* https://chat.openai.com/chat [accessed 13 March 2025].
*/
function renderGrid() {
    let gridContainer = document.getElementById("grid");
    gridContainer.innerHTML = ""; // Clear the existing grid content to avoid duplication.

    // Loop through each row of the grid.
    for (let i = 0; i < GRID_SIZE; i++) {
        // Loop through each column of the grid.
        for (let j = 0; j < GRID_SIZE; j++) {
            let cell = document.createElement("div");
            cell.classList.add("cell"); // Add the 'cell' class for styling.

            // Check the value of the current cell and display the corresponding object.
            if (grid[i][j] === "h") {
                // If the cell contains the hunter, create an image element for the hunter.
                const hunterImg = document.createElement("img");
                hunterImg.src = "./hunter.png"; // Set the image source.
                hunterImg.alt = "Hunter"; // Add alt text for accessibility.
                hunterImg.style.width = "2.5em"; // Set the image size.
                hunterImg.style.height = "2.5em";
                hunterImg.style.objectFit = "contain"; // Ensure the image fits the cell.
                hunterImg.style.display = "block";
                cell.appendChild(hunterImg); // Append the image to the cell.
            } else if (grid[i][j] === "o") {
                // If the cell contains an obstacle, create an image element for the obstacle.
                const obstacleImg = document.createElement("img");
                obstacleImg.src = "./obstacle.png";
                obstacleImg.alt = "Obstacle";
                obstacleImg.style.width = "2.5em";
                obstacleImg.style.height = "2.5em";
                obstacleImg.style.objectFit = "contain";
                obstacleImg.style.display = "block";
                cell.appendChild(obstacleImg);
            } else if (grid[i][j] >= 5 && grid[i][j] <= 8) {
                // If the cell contains a treasure, create an image element for the treasure.
                const treasureImg = document.createElement("img");
                treasureImg.src = "./treasure.png";
                treasureImg.alt = "Treasure";
                treasureImg.style.width = "2.5em";
                treasureImg.style.height = "2.5em";
                treasureImg.style.objectFit = "contain";
                treasureImg.style.display = "block";
                cell.appendChild(treasureImg);
            } else {
                // If the cell is empty, set its text content to an empty string.
                cell.textContent = "";
            }

            // Add a click event listener to the cell to handle user interactions during setup.
            cell.addEventListener("click", function () {
                if (gameStage === "setup") {
                    // If the cell is already occupied, show an error message.
                    if (grid[i][j] !== 0) {
                        alert("Cell already occupied. You cannot change the object.");
                        return;
                    }

                    // Prompt the user to enter a value for the cell.
                    let userInput = prompt("Enter 5-8 for treasure, 'o' for obstacle, or 'h' for hunter:");
                    if (userInput !== null) {
                        // Validate the user input and place the corresponding object.
                        if (!isNaN(userInput) && userInput >= 5 && userInput <= 8) {
                            placeObject(i, j, parseInt(userInput)); // Place a treasure.
                        } else if (userInput === "o" || userInput === "h") {
                            placeObject(i, j, userInput); // Place an obstacle or hunter.
                        } else {
                            // Show an error message for invalid input.
                            alert("Invalid input! Enter a number between 5 and 8, 'o' for obstacle, or 'h' for hunter.");
                        }
                    }
                }
            });

            // Append the cell to the grid container.
            gridContainer.appendChild(cell);
        }
    }
}

// Function to update the status information displayed on the page.
// This includes the number of rounds played, treasures left, and the player's score.
function updateStatus() {
    document.getElementById("rounds").textContent = roundsCompleted;
    document.getElementById("treasures5").textContent = treasuresLeft[5];
    document.getElementById("treasures6").textContent = treasuresLeft[6];
    document.getElementById("treasures7").textContent = treasuresLeft[7];
    document.getElementById("treasures8").textContent = treasuresLeft[8];
    document.getElementById("score").textContent = score;
}

// Function to add a new obstacle to a randomly selected empty cell.
// This is called when the hunter collects a treasure.
function addRandomObstacle() {
    const emptyCells = [];
    // Find all empty cells in the grid.
    for (let i = 0; i < GRID_SIZE; i++) {
        for (let j = 0; j < GRID_SIZE; j++) {
            if (grid[i][j] === 0) {
                emptyCells.push({ x: i, y: j });
            }
        }
    }

    // If there are empty cells, place an obstacle in one of them.
    if (emptyCells.length > 0) {
        const randomIndex = Math.floor(Math.random() * emptyCells.length);
        const { x, y } = emptyCells[randomIndex];
        grid[x][y] = "o";
    }
}

// Function to handle key presses during the play stage.
// This allows the player to move the hunter using the WASD keys.
// W3Schools (13 March 2025), JavaScript String toLowerCase(). 
// Available at: https://www.w3schools.com/jsref/jsref_tolowercase.asp (Accessed: 13 March 2025).
function handleKeyPress(event) {
    if (gameStage !== "play") return;

    const key = event.key.toLowerCase();
    if (key === "w" || key === "a" || key === "s" || key === "d") {
        moveHunter(key); // Move the hunter in the specified direction.
    } else {
        alert("Invalid key! Use W(up), A (left), S (down) and D(right) keys to move the hunter.");
    }
}

// Function to move the hunter in the specified direction.
// This updates the hunter's position and handles interactions with treasures and obstacles.
function moveHunter(direction) {
    let newX = hunterX;
    let newY = hunterY;

    // Calculate the new position based on the direction.
    switch (direction) {
        case "w": newX--; break; // Move up.
        case "s": newX++; break; // Move down.
        case "a": newY--; break; // Move left.
        case "d": newY++; break; // Move right.
    }

    // Check if the new position is valid.
    if (!isValidCell(newX, newY)) {
        alert("Invalid move! You cannot move outside the grid.");
        return;
    }

    // Check if the new position contains an obstacle.
    if (grid[newX][newY] === "o") {
        alert("Invalid move! You cannot move onto an obstacle.");
        return;
    }

    // Move the hunter to the new position.
    grid[hunterX][hunterY] = 0; // Clear the old position.
    hunterX = newX;
    hunterY = newY;

    // Check if the new position contains a treasure.
    if (grid[newX][newY] >= 5 && grid[newX][newY] <= 8) {
        const treasureValue = grid[newX][newY];
        score += treasureValue; // Add the treasure's value to the score.
        treasuresLeft[treasureValue]--; // Decrement the count of this treasure type.
        grid[newX][newY] = "h"; // Place the hunter on the treasure cell.
        addRandomObstacle(); // Add a new obstacle to the grid.
    } else {
        grid[newX][newY] = "h"; // Place the hunter on the empty cell.
    }

    roundsCompleted++; // Increment the number of rounds completed.
    updateStatus(); // Update the status display.
    renderGrid(); // Re-render the grid to reflect the changes.
    checkGameEnd(); // Check if the game should end.
}

// Function to check if the game should end.
// The game ends if there are no treasures left or the hunter cannot move.
// W3Schools (13 March 2025), JavaScript Object.values(). 
// Available at: https://www.w3schools.com/jsref/jsref_object_values.asp (Accessed: 13 March 2025).
// W3Schools (13 March 2025), JavaScript Array every(). 
// Available at:https://www.w3schools.com/jsref/jsref_every.asp (Accessed: 13 March 2025).
// Bro Code (1 January 2024), JavaScript Full Course for free 🌐 (2024). 
// Available at:https://www.youtube.com/watch?v=lfmg-EJ8gm4&t=33901s (Accessed: 10 March 2025).
function checkGameEnd() {
    // Check if all treasures have been collected.
    if (Object.values(treasuresLeft).every(count => count === 0)) {
        endGame();
        return;
    }

    // Check if the hunter can move in any direction.
    const directions = [
        { x: hunterX - 1, y: hunterY }, // Up.
        { x: hunterX + 1, y: hunterY }, // Down.
        { x: hunterX, y: hunterY - 1 }, // Left.
        { x: hunterX, y: hunterY + 1 }, // Right.
    ];

    const canMove = directions.some(({ x, y }) => isValidCell(x, y) && grid[x][y] !== "o");

    // If the hunter cannot move, end the game.
    if (!canMove) {
        endGame();
    }
}

// Function to end the game and display the performance index.
function endGame() {
    gameStage = "end";
    document.removeEventListener("keydown", handleKeyPress); // Disable movement.

    // Calculate the performance index.
    const performanceIndex = roundsCompleted > 0 ? (score / roundsCompleted).toFixed(2) : 0;
    alert(`Game Over! Performance Index: ${performanceIndex}`);

    // Display the performance index in the footer.
    document.getElementById("performanceIndex").textContent = `Performance Index: ${performanceIndex}`;
}

// Function to start the game by transitioning from the setup stage to the play stage.
function EndSetUp() {
    if (hunterX === -1) {
        alert("You must place the hunter before starting the game!");
        return;
    }

    gameStage = "play";
    alert("Game started! Use W(up), A (left), S (down) and D(right) keys to move the hunter and collect treasures.");
    document.addEventListener("keydown", handleKeyPress); // Enable movement.
}

// Function to end the play stage and transition to the end stage.
function EndPlay() {
    if (gameStage !== "play") {
        alert("Play stage ended :(");
        return;
    }

    gameStage = "end";
    document.removeEventListener("keydown", handleKeyPress); // Disable movement.

    // Calculate the performance index.
    const performanceIndex = roundsCompleted > 0 ? (score / roundsCompleted).toFixed(2) : 0;
    alert(` The  is game over! Here's your Performance Index: ${performanceIndex}`);

    // Display the performance index in the footer.
    document.getElementById("performanceIndex").textContent = `Performance Index: ${performanceIndex}`;
}

// Initialise the game when the DOM is fully loaded.
document.addEventListener("DOMContentLoaded", function () {
    renderGrid(); // Render the initial grid.
    updateStatus(); // Update the status display.
});