with open('C:\\Users\\Rodion\\.qoder\\worktree\\wall.cyka.lol\\qoder\\check-social-controller-1762705469\\src\\Controllers\\SocialController.php', 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Remove lines that contain only the conflict marker
lines = [line for line in lines if line.strip() != '=======']

with open('C:\\Users\\Rodion\\.qoder\\worktree\\wall.cyka.lol\\qoder\\check-social-controller-1762705469\\src\\Controllers\\SocialController.php', 'w', encoding='utf-8') as f:
    f.writelines(lines)