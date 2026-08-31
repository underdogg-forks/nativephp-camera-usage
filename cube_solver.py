import sys
import json
try:
    import kociemba
except ImportError:
    print(json.dumps({'error': 'kociemba library not installed'}))
    sys.exit(1)

def solve(cube_string):
    # cube_string must be 54 chars in U R F D L B order
    # Our Laravel app uses U, R, F, D, L, B characters.
    if len(cube_string) != 54:
        print(json.dumps({'error': 'Invalid cube string length'}))
        return

    try:
        solution = kociemba.solve(cube_string)
        # solution is a string like "R U2 F' L B2 ..."
        moves = solution.split(' ')
        print(json.dumps({'solution': moves}))
    except Exception as e:
        print(json.dumps({'error': str(e)}))

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'error': 'No cube string provided'}))
        sys.exit(1)
        
    solve(sys.argv[1])
