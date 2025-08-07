# ViewPrint

> Every gaze tells a story - Analyze eye-tracking patterns on volumetric images

ViewPrint is a web-based platform for researchers studying how people view and interpret medical imaging data. By overlaying eye-tracking data on NIfTI volumes, ViewPrint reveals the hidden patterns that distinguish expert from novice viewers.

![ViewPrint Demo](docs/images/viewprint-demo.gif)
*Comparing expert vs novice viewing patterns on brain MRI*

## 🎯 Key Features

- **Flexible Workspaces** - Combine any mix of participants and NIfTI volumes for analysis
- **Layer System** - Stack multiple visualizations: base volumes, eye-tracking heatmaps, statistical overlays
- **Real-time Analysis** - See gaze patterns, fixations, and scanpaths overlaid on volumetric data
- **Statistical Tools** - Generate difference maps, group averages, and significance testing
- **Command Palette UI** - Keyboard-driven interface for efficient research workflows
- **Any NIfTI Support** - Works with MRI, CT, PET, fMRI, or custom volumetric data
- **Reproducible Research** - Save and share workspace configurations

## 🚀 Quick Start

### Prerequisites

- Docker Desktop
- Git
- Composer (for initial setup)

### Installation

1. Clone the repository
```bash
git clone https://github.com/yourusername/viewprint.git
cd viewprint
```

2. Install PHP dependencies
```bash
composer install
```

3. Copy environment file
```bash
cp .env.example .env
```

4. Start Laravel Sail
```bash
./vendor/bin/sail up -d
```

5. Generate application key
```bash
./vendor/bin/sail artisan key:generate
```

6. Run migrations
```bash
./vendor/bin/sail artisan migrate
```

7. Install frontend dependencies
```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

8. Visit http://localhost

## 📖 Documentation

Comprehensive documentation is available in the `docs/` directory:

- [Getting Started Guide](docs/user-guide/getting-started.md)
- [Architecture Overview](docs/architecture/overview.md)
- [API Documentation](docs/api/endpoints.md)
- [Algorithm Details](docs/algorithms/eye-tracking.md)
- [Development Guide](docs/developer/setup.md)

## 💻 Usage

### Basic Workflow

1. **Create a Workspace**
   - Press `Cmd+K` to open command palette
   - Type "new workspace" and select it
   - Name your analysis session

2. **Add a Base Volume**
   - Press `B` or use command "Add base volume"
   - Select your NIfTI file (MRI, CT, etc.)
   - Volume loads in the Niivue viewer

3. **Add Eye-Tracking Data**
   - Press `P` to add participant layers
   - Select participants and visualization type
   - Adjust opacity and colormaps as needed

4. **Analyze Patterns**
   - Toggle layers with number keys 1-9
   - Create calculated layers (differences, averages)
   - Export findings for publication

### Keyboard Shortcuts

| Key | Action |
|-----|--------|
| `Cmd+K` | Open command palette |
| `1-9` | Toggle layers 1-9 |
| `L` | Open layer panel |
| `F` | Open filter panel |
| `C` | Create calculated layer |
| `S` | Save workspace |
| `Space` | Play/pause animation |
| `R` | Reset view |

## 🛠️ Technology Stack

- **Backend**: Laravel 10.x with TALL Stack
  - **T**ailwind CSS - Utility-first styling
  - **A**lpine.js - Lightweight reactivity
  - **L**aravel - PHP framework
  - **L**ivewire - Full-stack reactive components
- **Viewer**: [Niivue](https://github.com/niivue/niivue) - WebGL neuroimaging viewer
- **Database**: MySQL/PostgreSQL
- **Cache/Queue**: Redis
- **Development**: Laravel Sail (Docker)
- **File Format**: NIfTI (.nii, .nii.gz)

## 🔧 Development

### Running Tests
```bash
./vendor/bin/sail test
```

### Code Style
```bash
./vendor/bin/sail php vendor/bin/pint
```

### Building Assets
```bash
# Development
./vendor/bin/sail npm run dev

# Production
./vendor/bin/sail npm run build
```

### Database Management
```bash
# Create a new migration
./vendor/bin/sail artisan make:migration create_workspaces_table

# Run migrations
./vendor/bin/sail artisan migrate

# Seed sample data
./vendor/bin/sail artisan db:seed
```

## 📊 Example Analyses

ViewPrint enables various research workflows:

- **Expertise Comparison**: Compare how radiologists vs residents view the same cases
- **Learning Analysis**: Track how viewing patterns change during training
- **Diagnostic Patterns**: Identify which regions experts focus on for specific conditions
- **Cross-Modal Studies**: Analyze viewing patterns across different imaging modalities

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Write tests and documentation
4. Commit your changes (`git commit -m 'Add amazing feature'`)
5. Push to the branch (`git push origin feature/amazing-feature`)
6. Open a Pull Request

### Development Philosophy

- **Documentation First**: Write docs before code
- **Test Driven**: Write tests for new features
- **Accessible**: Follow WCAG guidelines
- **Performance**: Keep layer rendering at 60fps

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Niivue](https://github.com/niivue/niivue) team for the excellent NIfTI viewer
- [Laravel](https://laravel.com) and the TALL stack community
- Researchers who provided feedback on workflows

## 📬 Contact

- **Issues**: [GitHub Issues](https://github.com/yourusername/viewprint/issues)
- **Discussions**: [GitHub Discussions](https://github.com/yourusername/viewprint/discussions)
- **Email**: viewprint@yourdomain.com

## 🗺️ Roadmap

See our [public roadmap](https://github.com/yourusername/viewprint/projects/1) for upcoming features:

- [ ] Real-time collaboration
- [ ] Machine learning integration
- [ ] Surface rendering support
- [ ] VR viewing mode
- [ ] Plugin system

---

<p align="center">
Built with ❤️ for the medical imaging research community
</p>
