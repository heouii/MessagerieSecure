import re
import json
import sys
import os
from datetime import datetime

# Chemin absolu depuis le répertoire du projet Laravel
BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
log_file = os.path.join(BASE_DIR, 'storage/logs/laravel.log')

def parse_log(date_str):
    results = []
    date_pattern = re.compile(r'\[(\d{4}-\d{2}-\d{2}) (\d{2}:\d{2}:\d{2})\]')

    with open(log_file, 'r', encoding='utf-8') as f:
        for line in f:
            match = date_pattern.search(line)
            if match:
                log_date = match.group(1)
                if log_date == date_str:
                    results.append(line.strip())

    return results

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print("Usage: python parse_logs.py YYYY-MM-DD")
        sys.exit(1)

    date_filter = sys.argv[1]

    # Valider la date
    try:
        datetime.strptime(date_filter, '%Y-%m-%d')
    except ValueError:
        print("Date invalide, format attendu: YYYY-MM-DD")
        sys.exit(1)

    filtered_logs = parse_log(date_filter)

    output_file = os.path.join(BASE_DIR, f'storage/logs/filtered_logs_{date_filter}.json')
    with open(output_file, 'w', encoding='utf-8') as json_file:
        json.dump(filtered_logs, json_file, ensure_ascii=False, indent=2)

    print(f"{len(filtered_logs)} lignes trouvées pour la date {date_filter}. Fichier créé : {output_file}")
